<?php

namespace App\Http\Controllers;

use App\Notice;
use App\UploadedFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request){
        $mode = $request->get('mode', 'SHOP');
        $list = Notice::where('type', $mode)->orderBy('created_at', 'desc')->get();
        return view('notices.index', [
            'list' => $list,
            'mode' => $mode,
        ]);
    }

    public function create(Request $request){
        $mode = $request->get('mode', 'SHOP');
        return view('notices.create', [
            'model' => new Notice(),
            'mode' => $mode
        ]);
    }

    public function store(Request $request){
        $request->validate([
            'file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'number' => 'required',
            'date' => 'required|date_format:d/m/Y H:i',
            'address' => 'required',
            'lat' => 'required',
            'lng' => 'required',
        ]);

        $file = $request->file('file');
        $extension = $file->extension();
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs('uploads', $filename, 'public');
        if ($path === false) {
            throw new RuntimeException('No se pudo almacenar la fotografia.');
        }

        try {
            DB::transaction(function () use ($request, $file, $extension, $filename, $path) {
                $model = new Notice();
                $model->type = $request->get('mode');
                $model->number = $request->get('number');
                $model->address = $request->get('address');
                $model->lat = $request->get('lat');
                $model->lng = $request->get('lng');
                $model->notice_date = Carbon::createFromFormat('d/m/Y H:i', $request->get('date'));
                $model->save();

                $upload = new UploadedFile();
                $upload->name = $filename;
                $upload->path = $path;
                $upload->extension = $extension;
                $upload->size = $file->getSize();
                $upload->url = Storage::url($path);
                $upload->entity_type = 'NOTICE';
                $upload->entity_id = $model->id;
                $upload->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return redirect()->route('notices.index', ['mode' => $request->get('mode')]);
    }

    public function show(Request $request, $id){
        $model = Notice::findOrFail($id);
        $mode = $request->get('mode', 'SHOP');
        $photos = UploadedFile::where('entity_type', 'NOTICE')->where('entity_id', $id)->get();
        $photo = count($photos)>0?$photos[0]:null;
        return view('notices.show', [
            'model' => $model,
            'mode' => $mode,
            'photo' => $photo
        ]);
    }

    public function update(Request $request, $id){
        $mode = $request->get('mode', 'SHOP');
        $request->validate([
            'owner' => 'required',
            'document' => 'required',
            'date2' => 'required|date_format:d/m/Y',
        ]);

        $model = Notice::findOrFail($id);
        $model->owner = $request->get('owner');
        $model->document = $request->get('document');
        $date = Carbon::createFromFormat('d/m/Y', $request->get('date2'));
        $model->presentation_date = $date;
        $model->phone = $request->get('phone');
        $model->obs = $request->get('obs');
        $model->retirement = $request->get('retirement', 0)==1;
        $model->save();

        return redirect()->route('notices.index', ['mode' => $request->get('mode')]);
    }
}
