@include('casetas.assets')
@php($readOnly = $readOnly ?? false)
<div class="location-capture" data-location data-readonly="{{ $readOnly ? '1' : '0' }}">
    <div class="field-map" data-location-map aria-label="Mapa de ubicación"></div>
    <div class="row mt-3">
        <div class="col-sm-6 form-group"><label>Latitud <input class="form-control" type="number" step="any" name="lat" value="{{ old('lat', $location->lat ?? '') }}" data-lat {{ $readOnly ? 'readonly' : '' }}></label></div>
        <div class="col-sm-6 form-group"><label>Longitud <input class="form-control" type="number" step="any" name="lng" value="{{ old('lng', $location->lng ?? '') }}" data-lng {{ $readOnly ? 'readonly' : '' }}></label></div>
    </div>
    @unless($readOnly)
    <input type="hidden" name="location_source" value="{{ old('location_source', (isset($location->id) || isset($location->caseta_id)) ? 'EXISTING' : '') }}" data-source>
    <input type="hidden" name="accuracy" value="" data-accuracy>
    <input type="hidden" name="located_at" value="" data-located-at>
    <input type="hidden" name="location_confirmed" value="0" data-confirmed>
    <div class="field-actions"><button type="button" class="btn btn-outline-primary" data-gps>Capturar GPS</button><button type="button" class="btn btn-primary" data-confirm-location>Confirmar este punto</button></div>
    <p class="mt-2 mb-2" data-location-status role="status" aria-live="polite">Capture el GPS o seleccione la ubicación y confirme el punto.</p>
    <label class="w-100">Motivo de ubicación manual <input name="location_reason" class="form-control" maxlength="1000" value="{{ old('location_reason') }}" placeholder="Ej.: punto corregido frente a la caseta" data-location-reason></label>
    <small class="text-muted">La precisión corresponde al dispositivo. Si corrige el punto en el mapa, se registrará como ubicación manual.</small>
    @else
    <p class="text-muted">Fuente: {{ $location->location_source ?? 'Histórica' }} · Precisión: {{ isset($location->accuracy) ? $location->accuracy.' m' : 'No registrada' }}</p>
    @endunless
</div>
