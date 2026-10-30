<?php
namespace App\Services;

use App\Models\Caseta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CasetaLocation
{
    // The caller holds the caseta row lock in the same transaction as the document.
    public static function synchronize(Caseta $caseta, Model $document, string $origin): void
    {
        if ($document->location_source === 'EXISTING') { return; }
        if ($document->location_source === 'GPS' && $caseta->located_at && $document->located_at->lt($caseta->located_at)
            && (abs($caseta->lat - $document->lat) > 0.0000001 || abs($caseta->lng - $document->lng) > 0.0000001)) {
            throw ValidationException::withMessages(['location_source' => 'La caseta tiene una ubicación más reciente. Capture y confirme el punto nuevamente.']);
        }
        $fields = ['lat', 'lng', 'accuracy', 'location_source', 'located_at', 'location_reason'];
        $before = $caseta->only($fields);
        foreach ($fields as $field) { $caseta->$field = $document->$field; }
        if (!$caseta->isDirty($fields)) { return; }
        $caseta->save();
        CasetaAudit::record($caseta->id, 'LOCATION', 'Ubicación compartida actualizada desde '.$origin,
            ['before' => $before, 'after' => $caseta->only($fields), 'document_id' => $document->id]);
    }
}
