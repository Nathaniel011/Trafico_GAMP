<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{

    public function __construct(){
        $this->middleware('can:field-work')->only(['create', 'store', 'edit', 'update', 'setState']);
        $this->middleware('auth', ['except' => ['store', 'apiIndex']]);
    }

    public function index(){
        $complaints = Complaint::orderBy('created_at', 'desc')->get();
        return view('complaints.index', [
            'list' => $complaints
        ]);
    }

    public function show($id){
        $complaint = Complaint::findOrFail($id);
        return view('complaints.show', [
            'model' => $complaint
        ]);
    }

    public function setState(Request $request, $id){
        $newState = (string) $request->get('state', '');
        $complaint = Complaint::findOrFail($id);
        if(strlen($newState)>0 && ($newState == 'ACEPTADO' || $newState == "RECHAZADO")){
            $complaint->state = $newState;
            $complaint->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function store(Request $request){
        $request->validate([
           'plaque' => 'required|string|max:255',
           'transport_line' => 'required|string|max:255',
           'photo' => 'nullable|string|max:13981016',
           'notes' => 'nullable|string|max:5000'
        ], [
            'plaque.required' => 'Placa requerida.',
            'transport_line.required' => 'Linea requerida'
        ]);
        $user = $request->user();
        $complaint = new Complaint();
        $complaint->vehicle_plaque = $request->plaque;
        $complaint->route_code = $request->transport_line;
        $complaint->notes = $request->notes;
        $complaint->state = 'PENDIENTE';
        $photo = (string) $request->get('photo', '');
        if(strlen($photo)>0){
            $bytes = base64_decode($photo, true);
            $info = $bytes !== false ? @getimagesizefromstring($bytes) : false;
            if (!$info || strlen($bytes) > 10485760 || $info[0] * $info[1] > 20000000 ||
                !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['photo' => 'Seleccione una imagen PNG, JPEG o WebP válida de hasta 10 MB.']);
            }
            $image = @imagecreatefromstring($bytes);
            if (!$image) {
                throw \Illuminate\Validation\ValidationException::withMessages(['photo' => 'No se pudo leer la fotografía.']);
            }
            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            $path = 'uploads/complaints/complaint-'.\Illuminate\Support\Str::uuid().'.png';
            abort_unless(Storage::disk('public')->put($path, $png), 500, 'No se pudo guardar la fotografía.');
            $complaint->photo_path = $path;
        }
        $complaint->inspector_id = $user->id;
        $complaint->save();
        return response()->json(['success'=>true]);
    }

    public function apiIndex(Request $request){
        $user = $request->user();
        $complaints = Complaint::where('inspector_id', $user->id)->orderBy('created_at', 'desc')->get();
        return ComplaintResource::collection($complaints);
    }
}
