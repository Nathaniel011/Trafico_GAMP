@extends('layouts.admin')
@section('header')<h1>Caseta {{ $caseta->number }} <small>{{ config('features.caseta_dossier') ? 'Ficha e historial' : 'Resumen' }}</small></h1>
@endsection
@section('content')
@include('casetas.assets')
@php($state = $caseta->controlState())
<div class="card"><div class="card-header field-actions">
<a class="btn btn-outline-primary" href="{{ route('casetas.map') }}">Mapa</a>
@can('field-work')<a class="btn btn-primary" href="{{ route('notices.create', ['caseta_id'=>$caseta->id, 'mode'=>'SHOP']) }}">Nueva notificación</a><a class="btn btn-outline-primary" href="{{ route('casetas.edit', $caseta) }}">Actualizar ficha</a><a class="btn btn-outline-primary" href="{{ route('certificate.create', ['caseta_id'=>$caseta->id]) }}">Nueva inspección</a>@endcan
</div><div class="card-body"><div class="row"><div class="col-lg-5">
<span class="state-badge state-{{ $caseta->licenseColor() }}">{{ $caseta->licenseStatus() }}</span> <span class="state-badge state-{{ $state['color'] }}">{{ $state['label'] }}</span>
@if($caseta->declared_no_license)<p class="mt-2"><strong>X</strong> El propietario declara que no tiene licencia.</p>@endif
<dl class="field-details mt-3"><dt>Propietario</dt><dd>{{ $caseta->owner }}</dd><dt>Dirección</dt><dd>{{ $caseta->address }}</dd>
@if(config('features.caseta_dossier'))<dt>CI / documento</dt><dd>{{ $caseta->document }}</dd><dt>Contacto</dt><dd>{{ $caseta->phone ?: 'Sin registrar' }}</dd><dt>Primer registro</dt><dd>{{ $caseta->created_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}</dd>@endif
@if(config('features.recaudaciones'))<dt>Recaudaciones</dt><dd><a href="{{ route('recaudaciones.index', ['q'=>$caseta->number]) }}">Consultar verificaciones por licencia</a></dd>@endif
</dl>
<p style="white-space:pre-wrap">{{ $caseta->observations }}</p>
</div><div class="col-lg-7">@include('casetas.location', ['location'=>$caseta, 'readOnly'=>true])</div></div></div></div>

<div class="card"><div class="card-header"><h2 class="h5 mb-0">Licencias</h2></div><div class="card-body">
@forelse($caseta->licenses as $license)
@php($check = config('features.recaudaciones') ? $license->paymentChecks->first() : null)
<div class="border-bottom mb-3 pb-3"><p><strong>{{ $license->number }} / {{ $license->year }}</strong> · {{ $license->issued_on->format('d/m/Y') }} a {{ $license->expires_on->format('d/m/Y') }} · {{ $license->source }}@if(config('features.recaudaciones')) · <strong>{{ $check?->label() ?? 'Pago por verificar' }}</strong>@endif</p>
@if(config('features.recaudaciones'))
@if($check)<p>Consultado el {{ $check->checked_at->timezone('America/La_Paz')->format('d/m/Y H:i:s') }} · {{ $check->source }} · {{ $check->reference }}</p>@endif
<details><summary>Historial de verificaciones de pago ({{ $license->paymentChecks->count() }})</summary>@forelse($license->paymentChecks as $payment)<p>{{ $payment->checked_at->timezone('America/La_Paz')->format('d/m/Y H:i:s') }} · {{ $payment->label() }} · {{ $payment->source }} · {{ $payment->reference }}<br>{{ $payment->observations }}</p>@empty<p>No hay verificaciones de pago documentadas.</p>@endforelse</details>
@can('manage-system')<details class="mt-2"><summary>Registrar verificación de pago</summary><form class="field-filters mt-3" method="POST" action="{{ route('recaudaciones.store', [$caseta, $license]) }}">@csrf
<label>Estado<select class="form-control" name="estado" required><option value="UNVERIFIED">Por verificar</option><option value="PAID">Pagado</option><option value="PENDING">Pendiente</option><option value="EXEMPT">Exento</option></select></label>
<label>Fuente<input class="form-control" name="fuente" required maxlength="255"></label><label>Referencia de respaldo<input class="form-control" name="referencia" required maxlength="120"></label><label>Fecha de consulta (hora de Bolivia)<input class="form-control" name="verificado_el" placeholder="AAAA-MM-DD HH:MM:SS" value="{{ now('America/La_Paz')->format('Y-m-d H:i:s') }}" required></label><label>Observaciones<textarea class="form-control" name="observaciones" maxlength="2000"></textarea></label><button class="btn btn-primary">Guardar verificación</button></form></details>@endcan
@endif</div>
@empty<p>No hay licencias verificadas registradas.</p>@endforelse
@can('manage-system')
<details><summary>Registrar licencia verificada</summary><form class="field-filters mt-3" method="POST" action="{{ route('casetas.license', $caseta) }}">@csrf
<label>Número<input class="form-control" name="number" required maxlength="80"></label><label>Gestión<input class="form-control" type="number" name="year" value="{{ now('America/La_Paz')->year }}" min="2000" max="2200" required></label>
<label>Emisión<input class="form-control" name="issued_on" type="date" required></label><label>Vencimiento<input class="form-control" name="expires_on" type="date" required></label><label>Fuente de verificación<input class="form-control" name="source" required maxlength="255"></label><button class="btn btn-primary">Registrar licencia</button></form></details>
@endcan
</div></div>

<div class="card"><div class="card-header"><h2 class="h5 mb-0">Notificaciones y procedimientos</h2></div><div class="card-body">
@forelse($caseta->notices->sortByDesc('notice_date') as $notice)
@php($ns = $notice->controlState())
<p><a href="{{ route('notices.show', $notice) }}">{{ $notice->number }} / {{ $notice->year ?? 'Histórico' }}</a> · {{ $notice->formatted_date }} <span class="state-badge state-{{ $ns['color'] }}">{{ $ns['label'] }}</span>@if($notice->deadline_at) · Vence {{ $notice->deadline_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}@endif</p>
@empty<p>No hay notificaciones vinculadas.</p>@endforelse
@foreach($caseta->cases as $case)<details class="mb-3"><summary>Procedimiento #{{ $case->id }} · {{ $case->status === 'OPEN' ? 'Abierto' : 'Cerrado' }}</summary><p>{{ $case->reason }}</p>@if($case->status === 'CLOSED')<p>Cierre: {{ $case->closing_reason }}</p>@else @can('manage-system')<form method="POST" action="{{ route('casetas.case.close', [$caseta, $case]) }}">@csrf<label>Motivo de cierre<input class="form-control" name="reason" required maxlength="2000"></label><button class="btn btn-outline-danger" type="submit">Cerrar procedimiento</button></form>@endcan @endif</details>@endforeach
</div></div>

@if(config('features.caseta_dossier'))
<div class="card"><div class="card-header"><h2 class="h5 mb-0">Certificados de inspección</h2></div><div class="card-body">
@forelse($caseta->certificates as $certificate)<p><a href="{{ route('certificate.show', $certificate) }}">Certificado #{{ $certificate->id }}</a> · <a href="{{ route('certificate.history', $certificate) }}">Versiones</a> · {{ $certificate->inspection_date }} · {{ $certificate->solicitante }}</p>@empty<p>No hay certificados vinculados.</p>@endforelse
@can('manage-system')<details><summary>Vincular documentos históricos revisados</summary><p>Compruebe el documento antes de vincularlo. Esta operación no cambia su contenido ni deduce la secuencia de avisos.</p><div class="row">
<div class="col-md-6"><form method="POST" action="{{ route('casetas.link.certificate', $caseta) }}">@csrf<label>ID del certificado<input class="form-control" type="number" min="1" name="certificate_id" required></label><button class="btn btn-outline-primary">Vincular certificado</button></form></div>
<div class="col-md-6"><form method="POST" action="{{ route('casetas.link.notice', $caseta) }}">@csrf<label>ID del aviso<input class="form-control" type="number" min="1" name="notice_id" required></label><button class="btn btn-outline-primary">Vincular aviso histórico</button></form></div>
</div></details>@endcan
</div></div>

<div class="card"><div class="card-header"><h2 class="h5 mb-0">Historial de control</h2></div><div class="card-body"><ol class="field-timeline">
@foreach($caseta->events as $event)<li><time>{{ $event->created_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}</time><strong>{{ $event->description }}</strong><small>{{ $event->actor_type ? class_basename($event->actor_type).' #'.$event->actor_id : 'Sistema' }}</small><details><summary>Ver datos de la actuación</summary><pre class="field-event">{{ json_encode($event->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></li>@endforeach
</ol></div></div>
@endif
@endsection
