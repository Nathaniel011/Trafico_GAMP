@extends('layouts.admin')
@section('header')<h1>{{ $caseta->exists ? 'Actualizar' : 'Registrar' }} caseta</h1>@endsection
@section('content')
<div class="card"><form method="POST" action="{{ $caseta->exists ? route('casetas.update', $caseta) : route('casetas.store') }}">@csrf
<div class="card-body"><div class="row"><div class="col-lg-5">
@foreach(['number'=>'Número de caseta','owner'=>'Nombre del propietario','document'=>'CI / documento','phone'=>'Contacto','address'=>'Dirección'] as $field=>$label)
<div class="form-group"><label for="caseta-{{ $field }}">{{ $label }}</label><input class="form-control" id="caseta-{{ $field }}" name="{{ $field }}" value="{{ old($field, $caseta->$field) }}" maxlength="{{ in_array($field, ['number','document','phone']) ? 80 : 255 }}" {{ $field !== 'phone' ? 'required' : '' }}></div>
@endforeach
<label for="license-verification">Verificación de licencia</label>
<select class="form-control mb-3" id="license-verification" name="license_verification"><option value="UNKNOWN" @selected(old('license_verification', $caseta->license_verification) !== 'NO_LICENSE')>Pendiente de verificar / consultar licencias registradas</option><option value="NO_LICENSE" @selected(old('license_verification', $caseta->license_verification) === 'NO_LICENSE')>Sin licencia verificado</option></select>
<label class="d-block"><input type="checkbox" name="declared_no_license" value="1" @checked(old('declared_no_license', $caseta->declared_no_license))> El propietario declara que no cuenta con licencia (X)</label>
<label for="observations">Observaciones</label><textarea class="form-control" id="observations" name="observations" rows="4" maxlength="5000">{{ old('observations', $caseta->observations) }}</textarea>
</div><div class="col-lg-7"><h2 class="h5">Ubicación de la caseta</h2>@include('casetas.location', ['location' => $caseta])</div></div></div>
<div class="card-footer field-actions"><button class="btn btn-primary" type="submit">Guardar caseta</button><a class="btn btn-default" href="{{ route('casetas.index') }}">Cancelar</a></div>
</form></div>
@endsection
