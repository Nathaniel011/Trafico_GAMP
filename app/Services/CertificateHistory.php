<?php
namespace App\Services;

use App\Models\Caseta;
use App\Models\Certificate;
use App\Models\CertificateRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CertificateHistory
{
    public static function version(Certificate $certificate): string
    {
        $attributes = $certificate->getAttributes();
        ksort($attributes);
        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    public static function snapshot(Certificate $certificate, string $reason): CertificateRevision
    {
        $actor = auth()->user();
        return CertificateRevision::create([
            'certificate_id' => $certificate->id,
            'revision' => ($certificate->revisions()->max('revision') ?? 0) + 1,
            'snapshot' => $certificate->toArray(), 'reason' => $reason,
            'actor_type' => $actor ? get_class($actor) : null, 'actor_id' => $actor?->getAuthIdentifier(), 'created_at' => now(),
        ]);
    }

    public static function save(Certificate $draft, ?string $expectedVersion = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($draft, $expectedVersion, $reason) {
            $caseta = $draft->caseta_id ? Caseta::lockForUpdate()->findOrFail($draft->caseta_id) : null;
            $before = null;
            if ($draft->exists) {
                $current = Certificate::lockForUpdate()->findOrFail($draft->id);
                if (!$expectedVersion || !hash_equals(self::version($current), $expectedVersion)) {
                    throw ValidationException::withMessages(['certificate_version' => 'El certificado cambió desde que abrió el formulario. Recárguelo antes de guardar.']);
                }
                $before = $current->toArray();
                if (config('features.caseta_dossier') && !$current->revisions()->exists()) { self::snapshot($current, 'Contenido histórico anterior a la primera edición con historial'); }
            }
            if ($caseta && $draft->location_source === 'EXISTING'
                && (abs($caseta->lat - $draft->lat) > 0.0000001 || abs($caseta->lng - $draft->lng) > 0.0000001)) {
                throw ValidationException::withMessages(['location_source' => 'La ubicación compartida cambió. Recargue el formulario y confirme el punto actual.']);
            }
            $draft->save();
            $draft->refresh();
            $revision = config('features.caseta_dossier') ? self::snapshot($draft, $reason ?? 'Registro inicial de inspección') : null;
            if ($caseta) {
                CasetaLocation::synchronize($caseta, $draft, 'certificado #'.$draft->id);
                CasetaAudit::record($caseta->id, $before ? 'CERTIFICATE_UPDATED' : 'CERTIFICATE_CREATED', 'Inspección / certificado #'.$draft->id,
                    ['certificate_id' => $draft->id, 'revision' => $revision?->revision, 'reason' => $reason, 'before' => $before, 'after' => $draft->toArray()]);
            }
        }, 3);
    }
}
