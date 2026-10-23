<?php
namespace App\Http\Controllers;

use App\Models\Caseta;
use App\Models\ControlCase;
use App\Models\Certificate;
use App\Notice;
use App\Services\CasetaAudit;
use App\Services\LocationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CasetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:field-work')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('can:manage-system')->only(['license', 'closeCase', 'linkCertificate', 'linkNotice']);
    }

    private function query(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'license' => 'nullable|in:VIGENTE,SIN LICENCIA,NO VIGENTE,POR VERIFICAR', 'stage' => 'nullable|in:neutral,yellow,blue,green,red',
            'south' => 'nullable|numeric|between:-90,90', 'north' => 'nullable|numeric|between:-90,90', 'west' => 'nullable|numeric|between:-180,180', 'east' => 'nullable|numeric|between:-180,180']);
        $query = Caseta::with(['licenses', 'cases' => fn ($q) => $q->where('status', 'OPEN'), 'notices' => fn ($q) => $q->whereHas('controlCase', fn ($c) => $c->where('status', 'OPEN')), 'lastNotice'])->withCount('notices')->when($request->filled('q'), function ($query) use ($request) {
            $query->where(function ($q) use ($request) { foreach (['number', 'owner', 'document', 'address'] as $column) { $q->orWhere($column, 'like', '%'.$request->q.'%'); } });
        });
        $today = today('America/La_Paz')->toDateString();
        $valid = fn ($q) => $q->whereDate('issued_on', '<=', $today)->whereDate('expires_on', '>=', $today);
        switch ($request->license) {
            case 'VIGENTE': $query->whereHas('licenses', $valid); break;
            case 'SIN LICENCIA': $query->whereDoesntHave('licenses', $valid)->where(fn ($q) => $q->where('declared_no_license', true)->orWhere('license_verification', 'NO_LICENSE')); break;
            case 'NO VIGENTE': $query->whereDoesntHave('licenses', $valid)->whereHas('licenses')->where('declared_no_license', false)->where('license_verification', 'UNKNOWN'); break;
            case 'POR VERIFICAR': $query->whereDoesntHave('licenses')->where('declared_no_license', false)->where('license_verification', 'UNKNOWN'); break;
        }
        if ($request->filled('stage')) {
            $active = function ($q) {
                $q->whereHas('controlCase', fn ($c) => $c->where('status', 'OPEN'))
                    ->whereNotNull('sequence')->whereNotExists(function ($later) {
                        $later->selectRaw('1')->from('notices as later_notice')->whereColumn('later_notice.control_case_id', 'notices.control_case_id')->whereColumn('later_notice.sequence', '>', 'notices.sequence');
                    });
            };
            if ($request->stage === 'neutral') { $query->whereDoesntHave('notices', $active); }
            else {
                $stage = $request->stage;
                $query->whereHas('notices', function ($q) use ($active, $stage) {
                    $active($q);
                    if ($stage === 'red') { $q->where('sequence', 3)->where('deadline_at', '<=', now('UTC')); }
                    elseif ($stage === 'green') { $q->where('sequence', 3)->where('deadline_at', '>', now('UTC')); }
                    else { $q->where('sequence', $stage === 'yellow' ? 1 : 2); }
                });
            }
        }
        return $query;
    }

    public function index(Request $request)
    {
        $casetas = $this->query($request)->orderBy('number')->paginate(30)->withQueryString();
        return view('casetas.index', compact('casetas'));
    }
    public function map() { return view('casetas.map'); }

    public function points(Request $request)
    {
        $query = $this->query($request);
        if ($request->filled(['south', 'north', 'west', 'east'])) {
            $query->whereBetween('lat', [$request->south, $request->north])->whereBetween('lng', [$request->west, $request->east]);
        }
        $total = (clone $query)->count();
        $points = $query->orderBy('id')->limit(1000)->get()->map(fn ($c) => [
            'id' => $c->id, 'number' => $c->number, 'owner' => $c->owner, 'address' => $c->address, 'lat' => $c->lat, 'lng' => $c->lng,
            'license' => $c->licenseStatus(), 'license_color' => $c->licenseColor(), 'declared_no_license' => $c->declared_no_license,
            'control' => $c->controlState(), 'notice_count' => $c->notices_count, 'last_notice_at' => $c->lastNotice?->formatted_date, 'deadline_at' => $c->notices->where('sequence', 3)->first()?->deadline_at?->toIso8601String(), 'observations' => mb_substr((string) $c->observations, 0, 500), 'url' => route('casetas.show', $c)
        ]);
        $legacy = CasetaLegacyController::points($request, max(0, 1000 - $points->count()));
        return response()->json(['points' => $points, 'total' => $total, 'legacy_points' => $legacy['points'], 'legacy_total' => $legacy['total'], 'truncated' => ($total + $legacy['total']) > 1000, 'server_time' => now()->toIso8601String()])->header('Cache-Control', 'private, no-store');
    }

    public function create() { return view('casetas.form', ['caseta' => new Caseta()]); }
    public function edit(Caseta $caseta) { return view('casetas.form', compact('caseta')); }

    private function data(Request $request, ?Caseta $caseta = null): array
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:80', Rule::unique('casetas')->ignore($caseta?->id)],
            'owner' => 'required|string|max:255', 'document' => 'required|string|max:80', 'phone' => 'nullable|string|max:80',
            'address' => 'required|string|max:255', 'license_verification' => 'nullable|in:UNKNOWN,NO_LICENSE', 'declared_no_license' => 'nullable|boolean', 'observations' => 'nullable|string|max:5000',
        ] + LocationRules::rules(true));
        LocationRules::verifyExisting($request, $caseta);
        unset($data['location_confirmed']);
        $data['license_verification'] = $data['license_verification'] ?? ($caseta?->license_verification ?? 'UNKNOWN');
        $data['declared_no_license'] = $request->boolean('declared_no_license');
        if ($data['location_source'] === 'EXISTING') {
            $data['located_at'] = $caseta->located_at;
            $data['accuracy'] = $caseta->accuracy;
            $data['location_source'] = $caseta->location_source;
            $data['location_reason'] = $caseta->location_reason;
        } else {
            $data['located_at'] = $data['location_source'] === 'GPS' ? \Carbon\Carbon::parse($data['located_at'])->utc() : now('UTC');
            if ($data['location_source'] !== 'GPS') { $data['accuracy'] = null; }
        }
        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $caseta = DB::transaction(function () use ($data) {
            $caseta = Caseta::create($data);
            CasetaAudit::record($caseta->id, 'REGISTERED', 'Registro inicial de caseta', $caseta->toArray());
            return $caseta;
        });
        return redirect()->route('casetas.show', $caseta)->with('success', 'Caseta registrada.');
    }

    public function update(Request $request, Caseta $caseta)
    {
        DB::transaction(function () use ($request, $caseta) {
            $locked = Caseta::lockForUpdate()->findOrFail($caseta->id);
            $data = $this->data($request, $locked);
            $before = $locked->toArray();
            $locked->update($data);
            CasetaAudit::record($locked->id, 'UPDATED', 'Actualización de ficha o ubicación', ['before' => $before, 'after' => $locked->toArray()]);
        });
        return redirect()->route('casetas.show', $caseta)->with('success', 'Datos de la caseta actualizados.');
    }

    public function show(Caseta $caseta)
    {
        $caseta->load(['licenses', 'cases', 'notices']);
        if (config('features.caseta_dossier')) { $caseta->load(['certificates', 'events']); }
        if (config('features.recaudaciones')) { $caseta->load('licenses.paymentChecks'); }
        return view('casetas.show', compact('caseta'));
    }

    public function license(Request $request, Caseta $caseta)
    {
        $data = $request->validate(['number' => 'required|string|max:80', 'year' => 'required|integer|between:2000,2200',
            'issued_on' => 'required|date_format:Y-m-d', 'expires_on' => 'required|date_format:Y-m-d|after_or_equal:issued_on', 'source' => 'required|string|max:255']);
        DB::transaction(function () use ($caseta, $data) {
            Caseta::lockForUpdate()->findOrFail($caseta->id);
            abort_if($caseta->licenses()->where('number', $data['number'])->where('year', $data['year'])->exists(), 422, 'Esta licencia ya está registrada.');
            $license = $caseta->licenses()->create($data);
            CasetaAudit::record($caseta->id, 'LICENSE', 'Registro de licencia', $license->toArray());
        });
        return back()->with('success', 'Licencia registrada.');
    }

    public function closeCase(Request $request, Caseta $caseta, ControlCase $case)
    {
        abort_unless($case->caseta_id === $caseta->id, 404);
        $data = $request->validate(['reason' => 'required|string|max:2000']);
        DB::transaction(function () use ($caseta, $case, $data) {
            Caseta::lockForUpdate()->findOrFail($caseta->id);
            abort_unless($case->fresh()->status === 'OPEN', 422, 'El procedimiento ya está cerrado.');
            $case->update(['status' => 'CLOSED', 'closed_at' => now(), 'closing_reason' => $data['reason']]);
            CasetaAudit::record($caseta->id, 'CASE_CLOSED', 'Cierre de procedimiento', ['case_id' => $case->id, 'reason' => $data['reason']]);
        });
        return back()->with('success', 'Procedimiento cerrado. El historial se conserva.');
    }

    public function linkCertificate(Request $request, Caseta $caseta)
    {
        $request->validate(['certificate_id' => 'required|integer|exists:certificates,id']);
        DB::transaction(function () use ($request, $caseta) {
            Caseta::lockForUpdate()->findOrFail($caseta->id);
            $cert = Certificate::lockForUpdate()->findOrFail($request->certificate_id);
            abort_if($cert->caseta_id, 422, 'El certificado ya está vinculado.');
            if (!$cert->revisions()->exists()) { \App\Services\CertificateHistory::snapshot($cert, 'Contenido histórico antes de vincular a la caseta'); }
            $cert->caseta_id = $caseta->id;
            Certificate::withoutTimestamps(fn () => $cert->save());
            \App\Services\CertificateHistory::snapshot($cert, 'Vinculación revisada a caseta ' . $caseta->number);
            CasetaAudit::record($caseta->id, 'CERTIFICATE_LINKED', 'Vinculación manual de certificado histórico', ['certificate_id' => $cert->id, 'inspection_date' => $cert->inspection_date, 'snapshot' => $cert->toArray()]);
        });
        return back()->with('success', 'Certificado vinculado sin cambiar su contenido.');
    }

    public function linkNotice(Request $request, Caseta $caseta)
    {
        $request->validate(['notice_id' => 'required|integer|exists:notices,id']);
        DB::transaction(function () use ($request, $caseta) {
            Caseta::lockForUpdate()->findOrFail($caseta->id);
            $notice = Notice::lockForUpdate()->findOrFail($request->notice_id);
            abort_if($notice->caseta_id || $notice->type === 'WAYS', 422, 'El aviso no admite esta vinculación.');
            $notice->caseta_id = $caseta->id;
            Notice::withoutTimestamps(fn () => $notice->save());
            CasetaAudit::record($caseta->id, 'NOTICE_LINKED', 'Vinculación de aviso histórico sin inferir secuencia ni vencimiento', ['notice_id' => $notice->id, 'original_notice_date' => $notice->getRawOriginal('notice_date'), 'snapshot' => $notice->toArray()]);
        });
        return back()->with('success', 'Aviso vinculado como histórico; no inicia un plazo automático.');
    }
}
