@extends('layouts.admin')
@section('header')<h1>Mapa de casetas <small>Licencias y seguimiento</small></h1>@endsection
@section('content')
@include('casetas.assets')
<div class="card"><div class="card-header field-actions"><a class="btn btn-outline-primary" href="{{ route('casetas.index') }}">Ver listado</a>@can('field-work')<a class="btn btn-primary" href="{{ route('casetas.create') }}">Registrar caseta</a>@endcan</div>
<div class="card-body" data-caseta-map-panel data-points-url="{{ route('casetas.points') }}">
@include('casetas.filters')
<div class="map-legend" aria-label="Leyenda"><span class="state-badge state-white">Licencia vigente</span><span class="state-badge state-red">Sin licencia / no vigente / pendiente de retiro</span><span class="state-badge state-yellow">1.er aviso</span><span class="state-badge state-blue">2.º aviso</span><span class="state-badge state-green">3.er aviso</span><span class="state-badge state-red">48 h después del 3.º</span><span class="state-badge state-neutral">Por verificar / sin avisos activos</span><span>X: declara no tener licencia</span></div>
<p data-map-status role="status" aria-live="polite">Cargando casetas…</p>
<div class="control-map" data-caseta-map aria-label="Mapa interactivo de casetas"></div>
<p class="text-muted mt-2">El cuerpo del marcador indica la licencia; el distintivo interior indica el aviso. Abra la caseta para consultar su resumen. Los círculos grises discontinuos son documentos históricos pendientes de vincular; su ubicación y licencia requieren revisión.</p>
</div></div>
@endsection
