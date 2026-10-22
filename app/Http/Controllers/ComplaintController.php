<?php

namespace App\Http\Controllers;

use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{

    public function __construct()
    {
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
           'plaque' => 'required',
           'transport_line' => 'required'
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
            $photo_name = "complaint-".time().".png";
            $path = 'uploads/complaints/'.$photo_name;
            Storage::disk('public')->put($path, base64_decode($photo));
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
