<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SchemaCompatibility
{
    /** Read capabilities only; never create or alter an installed database. */
    public static function casetaControlAvailable(): bool
    {
        foreach (['casetas', 'caseta_licenses', 'control_cases', 'caseta_events', 'temporary_photos'] as $table) {
            if (!Schema::hasTable($table)) return false;
        }
        return Schema::hasColumn('casetas', 'license_verification')
            && Schema::hasColumns('notices', ['caseta_id', 'control_case_id', 'year', 'sequence', 'deadline_at', 'accuracy', 'location_source', 'located_at', 'location_reason'])
            && Schema::hasColumn('certificates', 'caseta_id');
    }

    public static function discardUnsupported(Model $model, array $optionalFields): void
    {
        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        foreach ($optionalFields as $field) {
            if (!in_array($field, $columns, true)) $model->offsetUnset($field);
        }
    }

    public static function certificateHistoryAvailable(): bool
    {
        return config('features.caseta_dossier') && Schema::hasTable('certificate_revisions');
    }
}
