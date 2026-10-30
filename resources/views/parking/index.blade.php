@extends('layouts.admin')
@section('header')<h1>{{ $kind === 'car' ? 'Parqueo de vehículos' : 'Parqueo de motocicletas' }}</h1>@endsection
@section('content')
@include('parking.assets')
<div class="card"><div class="card-header">@can('field-work')<a class="btn btn-primary" href="{{ route($prefix.'.create') }}">Nuevo parqueo</a>@endcan</div><div class="card-body">
<div data-parking-map data-points-url="{{ route($prefix.'.points') }}" data-kind="{{ $kind }}" data-fit-bounds="1" data-icon-url="{{ asset($kind === 'car' ? 'img/car.png' : 'img/moto.png') }}">
<p data-parking-status role="status">Consultando puntos…</p><div id="map" class="control-map" data-parking-canvas aria-label="Mapa de parqueos"></div><button type="button" class="btn btn-outline-primary my-2" data-parking-retry>Actualizar puntos</button>
</div>
<div class="table-responsive"><table class="table" id="main-table"><thead><tr><th>Fecha</th><th>Nombre</th><th>Ubicación</th>@can('field-work')<th>Acciones</th>@endcan</tr></thead><tbody></tbody></table></div></div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('plugins/DataTables-1.11.3/css/dataTables.bootstrap4.min.css') }}">@endpush
@push('scripts')
<script src="{{ asset('plugins/DataTables-1.11.3/js/jquery.dataTables.min.js') }}"></script>
<script>
$(function () { $('#main-table').DataTable({processing:true, serverSide:true, ajax:@json(route($prefix.'.data')), columns:[{data:'created_at',name:'created_at'},{data:'name',name:'name'},{data:'description',name:'description'}@can('field-work'),{data:'actions',name:'actions',orderable:false,searchable:false}@endcan],language:{url:@json(asset('plugins/DataTables-1.11.3/es.json'))}}); });
</script>
@endpush
