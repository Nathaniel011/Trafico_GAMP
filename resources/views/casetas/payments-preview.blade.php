@extends('layouts.admin')
@section('header')<h1>Revisar importación de Recaudaciones</h1>@endsection
@section('content')
<div class="card"><div class="card-body"><p>{{ count($rows) }} filas válidas. Revise los datos antes de incorporar las verificaciones. Las filas idénticas se guardan una sola vez; una verificación antigua conserva su fecha y no reemplaza el estado más reciente.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Caseta</th><th>Licencia / gestión</th><th>Estado</th><th>Fuente / referencia</th><th>Verificado (Bolivia)</th><th>Observaciones</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row['input']['caseta'] }}</td><td>{{ $row['input']['licencia'] }} / {{ $row['input']['gestion'] }}</td><td>{{ $row['input']['estado'] }}</td><td>{{ $row['input']['fuente'] }} · {{ $row['input']['referencia'] }}</td><td>{{ $row['input']['verificado_el'] }}</td><td>{{ $row['input']['observaciones'] }}</td></tr>@endforeach</tbody></table></div>
<form method="POST" action="{{ route('recaudaciones.import') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><button class="btn btn-primary">Confirmar importación revisada</button> <a class="btn btn-outline-secondary" href="{{ route('recaudaciones.index') }}">Volver</a></form><p class="text-muted mt-3">Esta vista previa vence en 15 minutos.</p></div></div>
@endsection
