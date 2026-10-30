@extends('layouts.admin')
@section('header')<h1>Inicio <small>Panel de trabajo municipal</small></h1>@endsection
@section('content')
<section class="work-intro" aria-labelledby="work-title">
    <div><span class="institutional-eyebrow">Jefatura de Tráfico y Vialidad</span><h2 id="work-title">Control municipal, en un solo lugar.</h2><p>Consulta las casetas, revisa las notificaciones y ubica los registros en el mapa. Elige un módulo para comenzar.</p><a class="btn btn-primary" href="{{ route('casetas.map') }}"><i class="fas fa-map-marked-alt mr-1" aria-hidden="true"></i> Abrir mapa de casetas</a></div>
    <img class="work-seal" src="{{ asset('img/potosi.png') }}" alt="Gobierno Autónomo Municipal de Potosí" width="110" height="100">
</section>
<div class="work-grid">
    <a class="work-link" href="{{ route('casetas.index') }}"><i class="fas fa-store" aria-hidden="true"></i><h3>Casetas</h3><p>Consulta propietarios, ubicaciones y el estado de las licencias.</p><span>Consultar casetas <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></span></a>
    <a class="work-link" href="{{ route('notices.index', ['mode'=>'SHOP']) }}"><i class="fas fa-clipboard-check" aria-hidden="true"></i><h3>Notificaciones</h3><p>Busca por número y gestión y revisa los avisos y sus plazos.</p><span>Ver notificaciones <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></span></a>
    <a class="work-link" href="{{ route('certificate.index') }}"><i class="fas fa-file-alt" aria-hidden="true"></i><h3>Certificaciones</h3><p>Consulta los certificados de inspección y sus documentos PDF.</p><span>Ver certificaciones <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></span></a>
</div>
@can('field-work')
<div class="card"><div class="card-body"><h2 class="h5 mb-3">Acciones frecuentes</h2><div class="work-shortcuts"><a href="{{ route('casetas.create') }}" class="btn btn-outline-primary"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Registrar caseta</a>@can('edit-certificates')<a href="{{ route('certificate.create') }}" class="btn btn-outline-primary"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Nueva certificación</a>@endcan<a href="{{ route('carparking.create') }}" class="btn btn-outline-primary">Registrar parqueo</a></div></div></div>
@endcan
@endsection
