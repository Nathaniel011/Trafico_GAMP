<?php
namespace App\Http\Controllers;

use App\Models\Caseta;
use App\Models\Certificate;
use App\Notice;
use Illuminate\Http\Request;

class CasetaLegacyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-system')->only('link');
    }

    public function index(Request $request)
    {
        $request->validate(['kind' => 'nullable|in:certificates,notices', 'q' => 'nullable|string|max:100']);
        $kind = $request->get('kind', 'certificates');
        $query = $kind === 'certificates' ? Certificate::query() : Notice::whereIn('type', ['SHOP', 'SHOPS']);
        $query->whereNull('caseta_id');
        if ($request->filled('q')) {
            $columns = $kind === 'certificates' ? ['solicitante', 'ci', 'direccion'] : ['number', 'owner', 'document', 'address'];
            $query->where(function ($q) use ($columns, $request) {
                foreach ($columns as $column) { $q->orWhere($column, 'like', '%'.$request->q.'%'); }
            });
        }
        $documents = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $counts = ['certificates' => Certificate::whereNull('caseta_id')->count(),
            'notices' => Notice::whereNull('caseta_id')->whereIn('type', ['SHOP', 'SHOPS'])->count()];
        return view('casetas.legacy', compact('documents', 'kind', 'counts'));
    }

    public function link(Request $request)
    {
        $request->validate(['kind' => 'required|in:certificates,notices', 'document_id' => 'required|integer|min:1',
            'caseta_number' => 'required|string|exists:casetas,number', 'review_confirmed' => 'accepted']);
        $caseta = Caseta::where('number', $request->caseta_number)->firstOrFail();
        $controller = app(CasetaController::class);
        if ($request->kind === 'certificates') {
            $request->merge(['certificate_id' => $request->document_id]);
            return $controller->linkCertificate($request, $caseta);
        }
        $request->merge(['notice_id' => $request->document_id]);
        return $controller->linkNotice($request, $caseta);
    }

    public static function points(Request $request, int $limit): array
    {
        if (($request->filled('license') && $request->license !== 'POR VERIFICAR') || ($request->filled('stage') && $request->stage !== 'neutral')) {
            return ['points' => [], 'total' => 0];
        }
        $points = collect();
        $total = 0;
        foreach (['certificates', 'notices'] as $kind) {
            $query = $kind === 'certificates' ? Certificate::query() : Notice::whereIn('type', ['SHOP', 'SHOPS']);
            $query->whereNull('caseta_id')->whereBetween('lat', [-90, 90])->whereBetween('lng', [-180, 180]);
            if ($request->filled(['south', 'north', 'west', 'east'])) {
                $query->whereBetween('lat', [$request->south, $request->north])->whereBetween('lng', [$request->west, $request->east]);
            }
            if ($request->filled('q')) {
                $columns = $kind === 'certificates' ? ['solicitante', 'ci', 'direccion'] : ['owner', 'document', 'address', 'number'];
                $query->where(function ($q) use ($columns, $request) { foreach ($columns as $column) { $q->orWhere($column, 'like', '%'.$request->q.'%'); } });
            }
            $total += (clone $query)->count();
            if ($points->count() >= $limit) { continue; }
            foreach ($query->orderBy('id')->limit($limit - $points->count())->get() as $doc) {
                $certificate = $kind === 'certificates';
                $points->push(['id' => $doc->id, 'kind' => $kind, 'label' => $certificate ? 'Certificado #'.$doc->id : 'Aviso '.$doc->number,
                    'lat' => (float) $doc->lat, 'lng' => (float) $doc->lng,
                    'owner' => $certificate ? $doc->solicitante : $doc->owner, 'address' => $certificate ? $doc->direccion : $doc->address,
                    'url' => route($certificate ? 'certificate.show' : 'notices.show', $doc->id)]);
            }
        }
        return ['points' => $points->all(), 'total' => $total];
    }
}
