@extends('layouts.admin')
@section('header')<h1>Historial del certificado #{{ $model->id }}</h1>@endsection
@section('content')
<div class="card"><div class="card-body"><a class="btn btn-outline-primary mb-3" href="{{ route('certificate.show', $model) }}">Certificado actual</a>
@forelse($revisions as $revision)
<details class="mb-3" @if($loop->first) open @endif><summary><strong>Versión {{ $revision->revision }}</strong> · {{ $revision->created_at->timezone('America/La_Paz')->format('d/m/Y H:i') }} · {{ $revision->reason }}</summary>
<p>Registrada por {{ $revision->actor_type ? class_basename($revision->actor_type).' #'.$revision->actor_id : 'Sistema' }}.</p>
<dl><dt>Propietario / CI</dt><dd>{{ $revision->snapshot['solicitante'] ?? '' }} · {{ $revision->snapshot['ci'] ?? '' }}</dd><dt>Fecha de inspección</dt><dd>{{ $revision->snapshot['inspection_date'] ?? '' }}</dd><dt>Ubicación</dt><dd>{{ $revision->snapshot['direccion'] ?? '' }} · {{ $revision->snapshot['lat'] ?? '' }}, {{ $revision->snapshot['lng'] ?? '' }}</dd><dt>Conclusiones</dt><dd style="white-space:pre-wrap">{{ $revision->snapshot['conclusions'] ?? '' }}</dd></dl>
<details><summary>Todos los datos de esta versión</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></details>
@empty<p>Este documento es anterior al historial de versiones. Su contenido actual se conservará antes de la primera edición.</p>@endforelse
{{ $revisions->links('pagination::bootstrap-4') }}</div></div>
@endsection
