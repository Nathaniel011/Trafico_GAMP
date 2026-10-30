@extends('layouts.admin')
@section('header')
    <h1>Nueva notificación <small>{{ $mode === 'WAYS' ? 'Ocupación de vías' : 'Casetas' }}</small></h1>
@endsection
@section('content')
    <div class="card">
        <form action="{{ route('notices.store', ['mode' => $mode]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="form-group">
                            <label for="number">Número oficial de notificación</label>
                            <input class="form-control" id="number" name="number" maxlength="80" value="{{ old('number') }}" required>
                            @error('number')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="date">Fecha y hora de la notificación (Bolivia)</label>
                            <input class="form-control" id="date" name="date" value="{{ old('date', now('America/La_Paz')->format('d/m/Y H:i')) }}" required placeholder="dd/mm/aaaa hh:mm">
                            @error('date')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="address">Dirección</label>
                            <input class="form-control" id="address" name="address" value="{{ old('address') }}" required maxlength="255">
                            @error('address')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="file">Fotografía de la notificación</label>
                            <input class="form-control-file" type="file" name="file" id="file" accept="image/jpeg,image/png,image/webp" capture="environment" required>
                            <small class="form-text text-muted">JPG, PNG o WEBP; máximo 10 MB.</small>
                            @error('file')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <h2 class="h5">Ubicación de la notificación</h2>
                        <p class="text-muted">En esta instalación se conservan las coordenadas. Los datos de precisión y origen de captura requieren columnas adicionales.</p>
                        @include('casetas.location', ['location' => null])
                        @foreach(['lat', 'lng', 'location_confirmed', 'location_source', 'accuracy', 'located_at', 'location_reason'] as $field)
                            @error($field)<div class="text-danger">{{ $message }}</div>@enderror
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer field-actions">
                <button type="submit" class="btn btn-primary">Guardar notificación</button>
                <a class="btn btn-default" href="{{ route('notices.index', ['mode' => $mode]) }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
