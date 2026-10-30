@extends('layouts.admin')
@section('header')<h1>Notificación {{ $model->number }} <small>{{ $model->year ?? 'Histórica' }}</small></h1>@endsection
@section('content')
@include('casetas.assets')
@php($state = $model->controlState())
<div class="card"><div class="card-header"><span class="state-badge state-{{ $state['color'] }}">{{ $state['label'] }}</span>@if($model->caseta) <a href="{{ route('casetas.show', $model->caseta) }}">Ficha de caseta {{ $model->caseta->number }}</a>@endif</div>
<div class="card-body"><div class="row"><div class="col-lg-6"><p>{{ $model->formatted_date }} · {{ $model->address }}</p>@if($model->deadline_at)<p>Vencimiento: {{ $model->deadline_at->timezone('America/La_Paz')->format('d/m/Y H:i') }} (48 horas después del tercer aviso).</p>@endif
@include('casetas.location', ['location'=>$model, 'readOnly'=>true])
@if($photo)<a href="{{ $photo->url }}" target="_blank" rel="noopener"><img class="field-photo-preview" src="{{ $photo->url }}" alt="Fotografía de la notificación"></a>@endif</div>
<div class="col-lg-6">
@can('field-work')
<form method="POST" action="{{ route('notices.update', $model->id) }}">@csrf
<fieldset>
@foreach(['owner'=>'Propietario','document'=>'CI / documento','phone'=>'Contacto'] as $field=>$label)<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $model->$field) }}" maxlength="{{ $field === 'owner' ? 255 : 80 }}" {{ $field !== 'phone' ? 'required' : '' }}></div>@endforeach
<label for="date2">Fecha de presentación</label><input class="form-control mb-3" id="date2" name="date2" value="{{ old('date2', $model->presentation_date?->format('d/m/Y')) }}" placeholder="dd/mm/aaaa">
<label for="obs">Observaciones</label><textarea class="form-control mb-3" id="obs" name="obs" rows="4" maxlength="5000">{{ old('obs', $model->obs) }}</textarea>
</fieldset>
@can('manage-system')
@php($selected = old('retirement', $model->retirement === null ? '' : (int) $model->retirement))
<label for="retirement">Procede retiro</label><select class="form-control mb-3" id="retirement" name="retirement"><option value="" @selected((string) $selected === '')>Pendiente de decisión</option><option value="1" @selected((string) $selected === '1')>Sí</option><option value="0" @selected((string) $selected === '0')>No</option></select>
@endcan
@can('field-work')<button class="btn btn-primary" type="submit">Guardar actualización</button>@endcan
</form>
@else
<dl class="field-details">
@foreach(['owner'=>'Propietario','document'=>'CI / documento','phone'=>'Contacto'] as $field=>$label)
<dt>{{ $label }}</dt><dd>{{ $model->$field ?: 'Sin registrar' }}</dd>
@endforeach
<dt>Fecha de presentación</dt><dd>{{ $model->presentation_date?->format('d/m/Y') ?: 'Sin registrar' }}</dd>
<dt>Observaciones</dt><dd style="white-space:pre-wrap">{{ $model->obs ?: 'Sin observaciones' }}</dd>
<dt>Procede retiro</dt><dd>{{ $model->retirement === null ? 'Pendiente de decisión' : ($model->retirement ? 'Sí' : 'No') }}</dd>
</dl>
@endcan
</div></div></div></div>
@endsection
