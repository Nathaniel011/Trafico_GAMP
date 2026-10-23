@extends('layouts.admin')
@section('header')<h1>Notificaciones <small>{{ $mode === 'WAYS' ? 'Ocupación de vías' : 'Casetas' }}</small></h1>@endsection
@section('content')
@include('casetas.assets')
<div class="card"><div class="card-header field-actions">@can('field-work')<a class="btn btn-primary" href="{{ route('notices.create', ['mode'=>$mode]) }}">Nueva notificación</a>@endcan<a class="btn btn-outline-primary" href="{{ route('casetas.map') }}">Mapa de casetas</a></div>
<div class="card-body">
<form method="GET" class="field-filters"><input type="hidden" name="mode" value="{{ $mode }}">
<label>Número oficial<input class="form-control" name="number" value="{{ request('number') }}" maxlength="80"></label>
<label>Gestión<input class="form-control" name="year" type="number" min="2000" max="2200" value="{{ request('year') }}" placeholder="Todas"></label>
<button class="btn btn-primary" type="submit">Buscar</button><a href="{{ route('notices.index', ['mode'=>$mode]) }}">Limpiar</a></form>
<p>{{ $list->total() }} notificaciones encontradas.</p>
<div class="table-responsive"><table class="table field-table" id="main-table"><thead><tr><th>Nro. / gestión</th><th>Fecha</th><th>Caseta / propietario</th><th>Dirección</th><th>Aviso y plazo</th><th>Retiro</th><th>Detalle</th></tr></thead><tbody>
@forelse($list as $model)
@php($state = $model->controlState())
<tr><td>{{ $model->number }} / {{ $model->year ?? 'Histórico' }}</td><td>{{ $model->formatted_date }}</td><td>@if($model->caseta)<a href="{{ route('casetas.show', $model->caseta) }}">Caseta {{ $model->caseta->number }}</a><br>@endif{{ $model->owner ?? 'Sin completar' }}</td><td>{{ $model->address }}</td><td><span class="state-badge state-{{ $state['color'] }}">{{ $state['label'] }}</span>@if($model->deadline_at)<br><small>{{ $model->deadline_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}</small>@endif</td><td>{{ $model->retirement === null ? 'Pendiente de decisión' : ($model->retirement ? 'Procede retiro' : 'Sin retiro') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('notices.show', $model) }}">Ver</a></td></tr>
@empty<tr><td colspan="7">No hay notificaciones para esta búsqueda.</td></tr>@endforelse
</tbody></table></div>{{ $list->links('pagination::bootstrap-4') }}
</div></div>
@endsection
