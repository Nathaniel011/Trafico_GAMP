<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tráfico y Vialidad · G.A.M. de Potosí</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @vite('resources/js/institutional.js')
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed gamp-ui institutional-admin">
@php($accessLabel = auth()->user()->can('manage-system') ? 'Administración' : (auth()->user()->can('field-work') ? 'Inspección y notificación' : 'Consulta'))
<a href="#contenido" class="skip-link">Saltar al contenido</a>
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light" aria-label="Barra de sesión">
        <button class="nav-link" type="button" data-widget="pushmenu" aria-label="Abrir o cerrar menú principal" aria-controls="institutional-sidebar" aria-expanded="false"><i class="fas fa-bars" aria-hidden="true"></i></button>
        <div class="topbar-institution">
            <strong>Gobierno Autónomo Municipal de Potosí</strong>
            <span>Jefatura de Tráfico y Vialidad</span>
        </div>
        <div class="topbar-actions">
            <span class="access-label">{{ $accessLabel }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn session-exit"><i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i> Salir</button>
            </form>
        </div>
    </nav>
    @include('layouts.partials.sidebar')
    <div class="content-wrapper">
        <section class="content-header" aria-label="Título de la página">
            <div class="container-fluid"><div class="row mb-1"><div class="col-12">@yield('header')</div></div></div>
        </section>
        <main class="content" id="contenido" tabindex="-1">
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Revise los datos:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
    <footer class="main-footer">
        <span>Gobierno Autónomo Municipal de Potosí</span>
        <span>Tráfico y Vialidad · Gestión municipal</span>
    </footer>
</div>
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/adminlte.min.js') }}"></script>
<script>
    $(function () { $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}}); });
</script>
@stack('scripts')
</body>
</html>
