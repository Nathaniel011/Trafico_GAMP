<?php
namespace App\Services;
use App\Models\CasetaEvent;
class CasetaAudit
{
    public static function record(int $casetaId, string $kind, string $description, array $details = [], ?string $key = null): void
    {
        $actor = auth()->user();
        $attributes = ['caseta_id' => $casetaId, 'kind' => $kind, 'description' => $description, 'details' => $details, 'actor_type' => $actor ? get_class($actor) : null, 'actor_id' => $actor?->getAuthIdentifier()];
        if ($key) { CasetaEvent::firstOrCreate(['event_key' => $key], $attributes); }
        else { CasetaEvent::create($attributes); }
    }
}
