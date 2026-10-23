<?php
namespace App\Console\Commands;
use App\Models\Caseta;
use App\Notice;
use App\Services\CasetaAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ExpireCasetaNotices extends Command
{
    protected $signature = 'casetas:expire';
    protected $description = 'Registrar una vez el vencimiento de la tercera notificación a las 48 horas';
    public function handle(): int
    {
        Notice::where('sequence', 3)->where('deadline_at', '<=', now('UTC'))->whereHas('controlCase', fn ($q) => $q->where('status', 'OPEN'))->chunkById(100, function ($notices) {
            foreach ($notices as $notice) {
                DB::transaction(function () use ($notice) {
                    Caseta::lockForUpdate()->findOrFail($notice->caseta_id);
                    if ($notice->controlCase()->where('status', 'OPEN')->exists()) {
                        CasetaAudit::record($notice->caseta_id, 'DEADLINE', 'Vencimiento de tercera notificación: requiere seguimiento', ['notice_id' => $notice->id, 'deadline_at' => $notice->deadline_at->toIso8601String()], 'notice-deadline-'.$notice->id);
                    }
                });
            }
        });
        return self::SUCCESS;
    }
}
