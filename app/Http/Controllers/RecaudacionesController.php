<?php
namespace App\Http\Controllers;

use App\Models\Caseta;
use App\Models\CasetaLicense;
use App\Services\LicensePayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecaudacionesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-system')->except('index');
    }

    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:80', 'year' => 'nullable|integer|between:2000,2200', 'status' => 'nullable|in:PAID,PENDING,EXEMPT,UNVERIFIED']);
        $licenses = CasetaLicense::with(['caseta', 'paymentChecks' => fn ($q) => $q->limit(1)])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('number', $request->q)->orWhereHas('caseta', fn ($c) => $c->where('number', $request->q))))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->year))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->orderByDesc('year')->orderBy('id')->paginate(30)->withQueryString();
        return view('casetas.recaudaciones', compact('licenses'));
    }

    public function template()
    {
        return response(implode(',', LicensePayments::COLUMNS)."\r\n", 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="plantilla-recaudaciones.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate(['file' => 'required|file|max:2048']);
        $rows = LicensePayments::readCsv($request->file('file')->getRealPath());
        $token = Str::random(64);
        $request->session()->put('payments_preview', ['rows' => $rows, 'token' => $token, 'actor' => $request->user()->id, 'expires' => now()->addMinutes(15)->timestamp]);
        return view('casetas.payments-preview', compact('rows', 'token'));
    }

    public function import(Request $request)
    {
        $request->validate(['token' => 'required|string|size:64']);
        $preview = $request->session()->get('payments_preview');
        abort_unless($preview && $preview['actor'] === $request->user()->id && $preview['expires'] >= now()->timestamp
            && hash_equals($preview['token'], $request->token), 422, 'La vista previa venció. Cargue nuevamente el CSV.');
        $added = DB::transaction(function () use ($preview) {
            $count = 0;
            // Revalidate references and timestamps at confirmation; all rows commit together.
            $rows = collect($preview['rows'])->sortBy('data.caseta_license_id');
            foreach ($rows as $row) { $count += (int) LicensePayments::record(LicensePayments::validate($row['input'])); }
            return $count;
        }, 3);
        $request->session()->forget('payments_preview');
        return redirect()->route('recaudaciones.index')->with('success', $added.' verificaciones incorporadas. Las repetidas se conservaron una sola vez.');
    }

    public function store(Request $request, Caseta $caseta, CasetaLicense $license)
    {
        abort_unless($license->caseta_id === $caseta->id, 404);
        $data = LicensePayments::validate(array_merge($request->only(['estado', 'fuente', 'referencia', 'verificado_el', 'observaciones']),
            ['caseta' => $caseta->number, 'licencia' => $license->number, 'gestion' => $license->year]));
        $added = LicensePayments::record($data);
        return back()->with('success', $added ? 'Verificación registrada con su fuente y fecha.' : 'Esta verificación ya estaba registrada.');
    }
}
