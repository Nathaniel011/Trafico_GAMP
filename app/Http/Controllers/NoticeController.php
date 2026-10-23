<?php
namespace App\Http\Controllers;

use App\Notice;
use App\UploadedFile;
use App\Models\Caseta;
use App\Models\TemporaryPhoto;
use App\Services\CasetaAudit;
use App\Services\LocationRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:field-work')->only(['create', 'store', 'update']);
    }

    private function mode(Request $request): string { return $request->get('mode') === 'WAYS' ? 'WAYS' : 'SHOP'; }

    public function index(Request $request)
    {
        $request->validate(['year' => 'nullable|integer|between:2000,2200', 'number' => 'nullable|string|max:80']);
        $mode = $this->mode($request);
        $query = Notice::with('caseta')->whereIn('type', $mode === 'SHOP' ? ['SHOP', 'SHOPS'] : ['WAYS']);
        if ($request->filled('year')) {
            $start = Carbon::create((int) $request->year, 1, 1, 0, 0, 0, config('app.timezone'));
            $query->where(function ($q) use ($start, $request) {
                $q->where('year', (int) $request->year)->orWhere(function ($old) use ($start) {
                    $old->whereNull('year')->where('notice_date', '>=', $start)->where('notice_date', '<', $start->copy()->addYear());
                });
            });
        }
        if ($request->filled('number')) { $query->where('number', $request->number); }
        $list = $query->orderByDesc('notice_date')->paginate(30)->withQueryString();
        return view('notices.index', compact('list', 'mode'));
    }

    public function create(Request $request)
    {
        $caseta = $request->filled('caseta_id') ? Caseta::findOrFail($request->caseta_id) : null;
        return view('notices.create', ['model' => new Notice(), 'mode' => $this->mode($request), 'caseta' => $caseta]);
    }

    public function store(Request $request)
    {
        $mode = $this->mode($request);
        $request->validate([
            'file' => 'required_without:photo_id|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240', 'photo_id' => 'nullable|uuid',
            'number' => 'required|string|max:80', 'date' => 'required|date_format:d/m/Y H:i', 'address' => 'required|string|max:255',
            'caseta_id' => ($mode === 'SHOP' ? 'required' : 'nullable').'|integer|exists:casetas,id', 'reason' => 'nullable|string|max:2000',
        ] + LocationRules::rules(true));
        $date = Carbon::createFromFormat('!d/m/Y H:i', $request->date, 'America/La_Paz');
        if ($date->greaterThan(now('America/La_Paz')->addMinute())) { throw ValidationException::withMessages(['date' => 'La notificación no puede tener fecha futura.']); }
        $year = $date->year;
        if (Notice::whereIn('type', $mode === 'SHOP' ? ['SHOP', 'SHOPS'] : ['WAYS'])->where('number', $request->number)->where(function ($q) use ($year) {
            $q->where('year', $year)->orWhere(fn ($old) => $old->whereNull('year')->where('notice_date', '>=', $year.'-01-01 00:00:00')->where('notice_date', '<', ($year + 1).'-01-01 00:00:00'));
        })->exists()) {
            throw ValidationException::withMessages(['number' => 'Este número ya existe en la gestión seleccionada.']);
        }
        $directPath = null;
        if (!$request->filled('photo_id')) {
            $file = $request->file('file');
            $directPath = $file->storeAs('caseta-photos', Str::uuid().'.'.$file->extension(), 'local');
            abort_unless($directPath, 500, 'No se pudo almacenar la foto.');
        }
        try {
            DB::transaction(function () use ($request, $mode, $date, $year, $directPath) {
                $caseta = $mode === 'SHOP' ? Caseta::lockForUpdate()->findOrFail($request->caseta_id) : null;
                LocationRules::verifyExisting($request, $caseta);
                $case = null;
                $sequence = null;
                if ($caseta) {
                    $case = $caseta->cases()->where('status', 'OPEN')->first();
                    if (!$case) {
                        if (!$request->filled('reason')) { throw ValidationException::withMessages(['reason' => 'Indique el motivo para abrir el procedimiento.']); }
                        $case = $caseta->cases()->create(['reason' => $request->reason]);
                        CasetaAudit::record($caseta->id, 'CASE_OPENED', 'Apertura de procedimiento', ['case_id' => $case->id, 'reason' => $request->reason]);
                    }
                    $last = $case->notices()->orderByDesc('sequence')->first();
                    $sequence = ($last?->sequence ?? 0) + 1;
                    if ($sequence > 3) { throw ValidationException::withMessages(['caseta_id' => 'El procedimiento ya tiene tres notificaciones. Requiere seguimiento o cierre por jefatura.']); }
                    if ($last && $date->copy()->utc()->lessThanOrEqualTo($last->notice_date)) { throw ValidationException::withMessages(['date' => 'La fecha debe ser posterior a la notificación anterior.']); }
                }
                $photo = null;
                if ($request->filled('photo_id')) {
                    $photo = TemporaryPhoto::lockForUpdate()->find($request->photo_id);
                    if (!$photo || $photo->user_id !== $request->user()->id || $photo->consumed_at || $photo->expires_at->isPast() || !Storage::disk('local')->exists($photo->path)) {
                        throw ValidationException::withMessages(['photo_id' => 'La fotografía no está disponible. Cárguela nuevamente.']);
                    }
                }
                $model = new Notice();
                $model->type = $mode;
                $model->number = $request->number;
                $model->year = $year;
                $model->caseta_id = $caseta?->id;
                $model->control_case_id = $case?->id;
                $model->sequence = $sequence;
                $model->address = $request->address;
                $model->lat = $request->lat;
                $model->lng = $request->lng;
                $model->accuracy = $request->location_source === 'EXISTING' ? $caseta?->accuracy : ($request->location_source === 'GPS' ? $request->accuracy : null);
                $model->location_source = $request->location_source;
                $model->located_at = $request->location_source === 'EXISTING' ? $caseta?->located_at : ($request->location_source === 'GPS' ? Carbon::parse($request->located_at)->utc() : now('UTC'));
                $model->location_reason = $request->location_reason;
                $model->notice_date = $date->copy()->utc();
                $model->deadline_at = $sequence === 3 ? $date->copy()->utc()->addHours(48) : null;
                $model->owner = $caseta?->owner;
                $model->document = $caseta?->document;
                $model->phone = $caseta?->phone;
                $model->save();
                $upload = new UploadedFile();
                $upload->path = $photo?->path ?? $directPath;
                $upload->name = basename($upload->path);
                $upload->extension = $photo?->extension ?? $request->file('file')->extension();
                $upload->size = $photo?->size ?? $request->file('file')->getSize();
                $upload->url = '';
                $upload->entity_type = 'NOTICE';
                $upload->entity_id = $model->id;
                $upload->save();
                $upload->url = route('notice-photos.show', $upload);
                $upload->save();
                if ($photo) { $photo->update(['consumed_at' => now()]); }
                if ($caseta) { \App\Services\CasetaLocation::synchronize($caseta, $model, 'notificación #'.$model->id); CasetaAudit::record($caseta->id, 'NOTICE', 'Registro de notificación '.$sequence, ['notice_id' => $model->id, 'case_id' => $case->id, 'number' => $model->number, 'year' => $year, 'lat' => $model->lat, 'lng' => $model->lng, 'deadline_at' => $model->deadline_at?->toIso8601String()]); }
            }, 3);
        } catch (\Throwable $e) {
            if ($directPath) { Storage::disk('local')->delete($directPath); }
            if ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['number' => 'El aviso ya fue registrado o su número ya existe en esta gestión. Consulte el listado antes de reintentar.']);
            }
            throw $e;
        }
        return redirect()->route('notices.index', ['mode' => $mode, 'year' => $year])->with('success', 'Notificación registrada.');
    }

    public function show(Request $request, $id)
    {
        $model = Notice::with('caseta')->findOrFail($id);
        return view('notices.show', ['model' => $model, 'mode' => $model->type === 'WAYS' ? 'WAYS' : 'SHOP', 'photo' => UploadedFile::where('entity_type', 'NOTICE')->where('entity_id', $id)->first()]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['owner' => 'required|string|max:255', 'document' => 'required|string|max:80', 'phone' => 'nullable|string|max:80', 'obs' => 'nullable|string|max:5000', 'date2' => 'nullable|date_format:d/m/Y', 'retirement' => 'nullable|boolean']);
        if ($request->filled('retirement')) { \Illuminate\Support\Facades\Gate::authorize('manage-system'); }
        DB::transaction(function () use ($request, $id) {
            $model = Notice::lockForUpdate()->findOrFail($id);
            $before = $model->toArray();
            foreach (['owner', 'document', 'phone', 'obs'] as $field) { $model->$field = $request->input($field); }
            $model->presentation_date = $request->filled('date2') ? Carbon::createFromFormat('!d/m/Y', $request->date2) : null;
            if ($request->user()->can('manage-system')) { $model->retirement = $request->filled('retirement') ? $request->boolean('retirement') : null; }
            $model->save();
            if ($model->caseta_id) {
                CasetaAudit::record($model->caseta_id, 'NOTICE_UPDATED', 'Actualización de notificación', ['before' => $before, 'after' => $model->toArray()]);
                if ($request->user()->can('manage-system') && $request->has('retirement')) {
                    $caseta = Caseta::lockForUpdate()->findOrFail($model->caseta_id);
                    $caseta->update(['pending_retirement' => $caseta->notices()->where('retirement', true)->exists()]);
                }
            }
        });
        return back()->with('success', 'Notificación actualizada.');
    }
}
