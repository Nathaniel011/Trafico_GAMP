<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class LocationRules
{
    public static function rules(bool $capture = false): array
    {
        $rules = ['lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180'];
        if ($capture) {
            $rules += [
                'location_confirmed' => 'accepted', 'location_source' => 'required|in:GPS,MANUAL,EXISTING',
                'accuracy' => 'required_if:location_source,GPS|nullable|numeric|gt:0|max:100000',
                'located_at' => ['bail', 'required_if:location_source,GPS', 'nullable', 'date', function ($attribute, $value, $fail) {
                    if (request('location_source') === 'GPS' && (\Carbon\Carbon::parse($value)->lt(now()->subMinutes(10)) || \Carbon\Carbon::parse($value)->gt(now()->addMinute()))) $fail('Capture una lectura GPS reciente.');
                }],
                'location_reason' => 'required_if:location_source,MANUAL|nullable|string|max:1000',
            ];
        }
        return $rules;
    }
    public static function verifyExisting(Request $request, $original): void
    {
        if ($request->location_source === 'EXISTING' && (!$original || abs((float) $original->lat - (float) $request->lat) > 0.0000001 || abs((float) $original->lng - (float) $request->lng) > 0.0000001)) {
            throw ValidationException::withMessages(['location_source' => 'La ubicación no coincide con el punto registrado. Capture el GPS o justifique una ubicación manual.']);
        }
    }
}
