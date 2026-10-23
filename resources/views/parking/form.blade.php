@extends('layouts.admin')
@section('header')<h1>{{ $kind === 'car' ? 'Parqueo de vehículos' : 'Parqueo de motocicletas' }} <small>{{ $model->exists ? 'Editar' : 'Nuevo' }}</small></h1>@endsection
@section('content')
@include('parking.assets')
<div class="card"><form method="POST" action="{{ $model->exists ? route($prefix.'.update', $model->id) : route($prefix.'.store') }}">@csrf
<div class="card-body"><div class="row"><div class="col-lg-5">
@foreach(['name'=>'Nombre','description'=>'Ubicación / dirección','zone'=>'Zona'] as $field=>$label)<div class="form-group"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $model->$field) }}" maxlength="255" required></div>@endforeach
<div class="row">@foreach(['quantity'=>'Espacios','free'=>'Espacios libres'] as $field=>$label)<div class="col-sm-6 form-group"><label for="{{ $field }}">{{ $label }}</label><input class="form-control" id="{{ $field }}" name="{{ $field }}" type="number" min="0" step="1" value="{{ old($field, $model->$field) }}"></div>@endforeach</div>
</div><div class="col-lg-7" data-parking-map data-points-url="{{ route($prefix.'.points') }}" data-kind="{{ $kind }}" data-exclude-id="{{ $model->id }}" data-icon-url="{{ asset($kind === 'car' ? 'img/car.png' : 'img/moto.png') }}">
<h2 class="h5">Ubicación confirmada del parqueo</h2>@include('casetas.location', ['location'=>$model])
<p class="text-muted mt-2" data-parking-status role="status">Consultando otros puntos…</p></div></div></div>
<div class="card-footer field-actions"><button class="btn btn-primary" type="submit">Guardar parqueo</button><a class="btn btn-default" href="{{ route($prefix.'.index') }}">Cancelar</a></div>
</form></div>
@endsection
