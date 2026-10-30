<?php
namespace App\Http\Controllers;
use App\Models\TemporaryPhoto;
use App\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class NoticePhotoController extends Controller
{
    public function __construct() { $this->middleware('auth'); $this->middleware('can:field-work')->only('store'); }
    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240']);
        $file = $request->file('file');
        $path = $file->storeAs('caseta-photos', Str::uuid().'.'.$file->extension(), 'local');
        abort_unless($path, 500, 'No se pudo almacenar la foto.');
        try { $photo = TemporaryPhoto::create(['id' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'path' => $path, 'extension' => $file->extension(), 'size' => $file->getSize(), 'expires_at' => now()->addDay()]); }
        catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return response()->json(['id' => $photo->id, 'url' => route('notice-photos.preview', $photo->id), 'expires_at' => $photo->expires_at->toIso8601String()], 201);
    }
    public function preview(Request $request, TemporaryPhoto $photo)
    {
        abort_unless($photo->user_id === $request->user()->id && !$photo->consumed_at && $photo->expires_at->isFuture(), 404);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);
        return response()->file(Storage::disk('local')->path($photo->path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function show(UploadedFile $photo)
    {
        abort_unless($photo->entity_type === 'NOTICE', 404);
        $disk = str_starts_with($photo->path, 'caseta-photos/') ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($photo->path), 404);
        return response()->file(Storage::disk($disk)->path($photo->path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
