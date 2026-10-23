@extends('layouts.admin')
@section('header')<h1>Casetas <small>Registro y control municipal</small></h1>@endsection
@section('content')
@include('casetas.assets')
<div class="card"><div class="card-header field-actions"><a class="btn btn-outline-primary" href="{{ route('casetas.map') }}">Abrir mapa</a>@can('field-work')<a class="btn btn-primary" href="{{ route('casetas.create') }}">Registrar caseta</a>@endcan</div>
<div class="card-body">@include('casetas.filters')
<p class="text-muted">{{ $casetas->total() }} casetas encontradas. @if(config('features.caseta_dossier'))<a href="{{ route('casetas.legacy') }}">Revisar certificados y avisos históricos pendientes de vincular.</a>@endif</p>
<div class="table-responsive"><table class="table field-table"><thead><tr><th>Caseta</th><th>Propietario</th><th>Ubicación</th><th>Licencia</th><th>Notificación</th></tr></thead><tbody>
@forelse($casetas as $caseta)
@php($state = $caseta->controlState())
<tr><td><a href="{{ route('casetas.show', $caseta) }}">{{ $caseta->number }}</a></td><td>{{ $caseta->owner }}</td><td>{{ $caseta->address }}</td><td><span class="state-badge state-{{ $caseta->licenseColor() }}">{{ $caseta->licenseStatus() }}</span>@if($caseta->declared_no_license)<strong aria-label="Declara no tener licencia"> X</strong>@endif</td><td><span class="state-badge state-{{ $state['color'] }}">{{ $state['label'] }}</span></td></tr>
@empty<tr><td colspan="5">No hay casetas para estos filtros. Puede registrar una caseta o cambiar la búsqueda.</td></tr>@endforelse
</tbody></table></div>
{{ $casetas->links('pagination::bootstrap-4') }}</div></div>
@endsection
