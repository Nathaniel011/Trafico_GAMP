<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ParkingLocation
{
    public static function apply(Request $request, Model $model): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
            'quantity' => 'nullable|integer|min:0',
            'free' => 'nullable|integer|min:0',
        ] + LocationRules::rules(true));
        LocationRules::verifyExisting($request, $model->exists ? $model : null);
        if (isset($data['quantity'], $data['free']) && $data['free'] > $data['quantity']) {
            throw ValidationException::withMessages(['free' => 'Los espacios libres no pueden superar el total.']);
        }
        $source = $data['location_source'];
        foreach (['name', 'description', 'zone', 'quantity', 'free', 'lat', 'lng', 'location_source', 'location_reason'] as $field) {
            if (array_key_exists($field, $data)) $model->$field = $data[$field];
        }
        $model->accuracy = $source === 'GPS' ? $data['accuracy'] : ($source === 'EXISTING' ? $model->accuracy : null);
        $model->located_at = $source === 'GPS' ? Carbon::parse($data['located_at'])->utc() :
            ($source === 'EXISTING' && $model->located_at ? $model->located_at : now('UTC'));
        SchemaCompatibility::discardUnsupported($model, ['accuracy', 'location_source', 'located_at', 'location_reason']);
    }
}
