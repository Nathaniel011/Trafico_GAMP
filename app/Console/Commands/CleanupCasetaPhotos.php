<?php
namespace App\Console\Commands;
use App\Models\TemporaryPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class CleanupCasetaPhotos extends Command
{
    protected $signature = 'casetas:cleanup-photos';
    protected $description = 'Limpiar fotografías temporales vencidas sin eliminar adjuntos definitivos';
    public function handle(): int
    {
        TemporaryPhoto::where('expires_at', '<=', now())->chunkById(100, function ($photos) {
            foreach ($photos as $photo) {
                DB::transaction(function () use ($photo) {
                    $locked = TemporaryPhoto::lockForUpdate()->find($photo->id);
                    if (!$locked || $locked->expires_at->isFuture()) return;
                    if (!$locked->consumed_at && Storage::disk('local')->exists($locked->path) && !Storage::disk('local')->delete($locked->path)) {
                        $this->warn('No se pudo limpiar una fotografía temporal.'); return;
                    }
                    $locked->delete();
                });
            }
        });
        return self::SUCCESS;
    }
}
