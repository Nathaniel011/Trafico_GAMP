@extends('layouts.admin')
@section('header')<h1>Nueva notificación <small>{{ $mode === 'WAYS' ? 'Ocupación de vías' : 'Casetas' }}</small></h1>@endsection
@section('content')
@if($mode === 'SHOP' && !$caseta)
<div class="card"><div class="card-body"><h2 class="h5">Seleccione la caseta desde su ficha</h2><p>Así los avisos comparten propietario y ubicación y se mantiene la secuencia 1, 2 y 3.</p><a class="btn btn-primary" href="{{ route('casetas.index') }}">Buscar caseta</a> <a class="btn btn-outline-primary" href="{{ route('casetas.create') }}">Registrar nueva caseta</a></div></div>
@else
<div class="card"><form action="{{ route('notices.store', ['mode'=>$mode]) }}" method="POST" enctype="multipart/form-data">@csrf
<input type="hidden" name="caseta_id" value="{{ $caseta?->id }}">
<div class="card-body"><div class="row"><div class="col-lg-5">
@if($caseta)<p><strong>Caseta {{ $caseta->number }}</strong> · {{ $caseta->owner }}</p><p>El sistema asignará el siguiente aviso del procedimiento abierto.</p>@endif
<div class="form-group"><label for="number">Número oficial de notificación</label><input class="form-control" id="number" name="number" maxlength="80" value="{{ old('number') }}" required></div>
<div class="form-group"><label for="date">Fecha y hora de la notificación (Bolivia)</label><input class="form-control" id="date" name="date" value="{{ old('date', now('America/La_Paz')->format('d/m/Y H:i')) }}" required placeholder="dd/mm/aaaa hh:mm"></div>
<div class="form-group"><label for="address">Dirección</label><input class="form-control" id="address" name="address" value="{{ old('address', $caseta?->address) }}" required maxlength="255"></div>
<div class="form-group"><label for="reason">Motivo del procedimiento</label><textarea class="form-control" id="reason" name="reason" maxlength="2000" rows="2">{{ old('reason') }}</textarea><small>Obligatorio al registrar el primer aviso de un procedimiento.</small></div>
<div data-photo-upload data-upload-url="{{ route('notice-photos.store') }}">
<label for="file">Fotografía de la notificación</label><input class="form-control-file" type="file" name="file" id="file" accept="image/jpeg,image/png,image/webp" capture="environment">
<input type="hidden" name="photo_id" value="{{ old('photo_id') }}">
<img class="field-photo-preview" alt="Vista previa de la fotografía seleccionada" @if(old('photo_id')) src="{{ route('notice-photos.preview', old('photo_id')) }}" @else hidden @endif>
<p role="status" aria-live="polite">{{ old('photo_id') ? 'Fotografía previamente cargada; confirme que es la correcta.' : 'Seleccione o tome una fotografía. Se cargará antes de guardar.' }}</p>
<button type="button" class="btn btn-outline-primary" data-photo-retry hidden>Reintentar carga</button>
</div></div><div class="col-lg-7"><h2 class="h5">Ubicación de la caseta</h2>@include('casetas.location', ['location'=>$caseta])</div></div></div>
<div class="card-footer field-actions"><button type="submit" class="btn btn-primary">Guardar notificación</button><a class="btn btn-default" href="{{ route('notices.index', ['mode'=>$mode]) }}">Cancelar</a></div>
</form></div>
@endif
@endsection
