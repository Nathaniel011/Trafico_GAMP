@extends('layouts.admin')
@section('header')<h1>Documentos pendientes de vincular</h1>@endsection
@section('content')
@include('casetas.assets')
<div class="card"><div class="card-body">
<div class="field-actions"><a class="btn btn-outline-primary" href="{{ route('casetas.index') }}">Casetas</a>@can('field-work')<a class="btn btn-primary" href="{{ route('casetas.create') }}">Registrar caseta revisada</a>@endcan</div>
<p class="mt-3">{{ $counts['certificates'] }} certificados y {{ $counts['notices'] }} avisos sin caseta vinculada. Compruebe el propietario, la ubicación y el documento. La coincidencia de CI o coordenadas no basta para unir registros.</p>
<form class="field-filters" method="GET"><label>Documento<select class="form-control" name="kind"><option value="certificates" @selected($kind === 'certificates')>Certificados</option><option value="notices" @selected($kind === 'notices')>Notificaciones</option></select></label><label>Buscar<input class="form-control" name="q" value="{{ request('q') }}" maxlength="100"></label><button class="btn btn-primary">Buscar</button></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Documento</th><th>Propietario / CI</th><th>Ubicación</th><th>Vinculación revisada</th></tr></thead><tbody>
@forelse($documents as $document)<tr><td><a href="{{ route($kind === 'certificates' ? 'certificate.show' : 'notices.show', $document->id) }}">{{ $kind === 'certificates' ? 'Certificado #'.$document->id : 'Aviso '.$document->number }}</a><br>{{ $kind === 'certificates' ? $document->inspection_date : $document->formatted_date }}</td><td>{{ $kind === 'certificates' ? $document->solicitante : $document->owner }}<br>{{ $kind === 'certificates' ? $document->ci : $document->document }}</td><td>{{ $kind === 'certificates' ? $document->direccion : $document->address }}<br>{{ $document->lat }}, {{ $document->lng }}</td><td>@can('manage-system')<form method="POST" action="{{ route('casetas.legacy.link') }}">@csrf<input type="hidden" name="kind" value="{{ $kind }}"><input type="hidden" name="document_id" value="{{ $document->id }}"><label>Número de caseta existente<input class="form-control" name="caseta_number" required maxlength="80"></label><label class="d-block"><input type="checkbox" name="review_confirmed" value="1" required> Documento y caseta comprobados</label><button class="btn btn-outline-primary">Vincular</button></form>@else Solo jefatura puede vincular documentos. @endcan</td></tr>
@empty<tr><td colspan="4">No hay documentos pendientes para esta búsqueda.</td></tr>@endforelse
</tbody></table></div>{{ $documents->links('pagination::bootstrap-4') }}
<p>La vinculación conserva el documento y sus fechas originales. Los avisos antiguos no reciben una secuencia ni un vencimiento automático.</p>
</div></div>
@endsection
