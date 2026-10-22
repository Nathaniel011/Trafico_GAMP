<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trafico y vialidad</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{URL::asset('plugins/fontawesome-free/css/all.min.css')}}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{URL::asset('css/adminlte.min.css')}}">
    <link rel="stylesheet" href="{{URL::asset('css/custom.css')}}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('img/favicon.ico')}}">

    @stack('styles')
</head>
<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <form action="/logout" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </button>
            </form>

            {{--            <li class="nav-item">--}}
{{--                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">--}}
{{--                    <i class="fas fa-th-large"></i>--}}
{{--                </a>--}}
{{--            </li>--}}
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="#" class="brand-link">

            <span class="brand-text font-weight-light">Trafico y vialidad</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{URL::asset('img/avatar.jpg')}}" class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{auth()->user()->name}}</a>
                </div>
            </div>

            <!-- SidebarSearch Form -->
            <div class="form-inline">
                <div class="input-group" data-widget="sidebar-search">
                    <input class="form-control form-control-sidebar" type="search" placeholder="Buscar..." aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-sidebar">
                            <i class="fas fa-search fa-fw"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->
                    <li class="nav-item">
                        <a href="{{route('home.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-home"></i>
                            <p>
                                Inicio
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('certificate.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>
                                Certificaciones
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link ">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p>
                                Notificaciones
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{route('notices.index', ['mode'=>'SHOPS'])}}" class="nav-link">
                                    <i class="nav-icon fa fa-circle"></i>
                                    <p>Casetas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{route('notices.index', ['mode'=>'WAYS'])}}" class="nav-link">
                                    <i class="nav-icon fa fa-circle"></i>
                                    <p>Ocupacion de vias</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('shops')}}" class="nav-link">
                            <i class="nav-icon fas fa-store"></i>
                            <p>
                                Casetas
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('carparking.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-car-side"></i>
                            <p>
                                Parqueo de Vehiculos
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('motoparking.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-motorcycle"></i>
                            <p>
                                Parqueo de Motos
                            </p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-directions"></i>
                            <p>
                                Zona de Restricción
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('complaints.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-exclamation-circle"></i>
                            <p>
                                Denuncias
                                @php
                                $counter = \App\Models\Complaint::where("state", "PENDIENTE")->count();
                                @endphp
                                @if($counter>0)
                                    <span class="badge badge-primary right">{{$counter}}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('notifications.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>
                                Avisos
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('groups.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                Sindicatos / Cooperativas
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{route('inspectors.index')}}" class="nav-link">
                            <i class="nav-icon fas fa-user-lock"></i>
                            <p>
                                Inspectores
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>
                                Reportes
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{route('reports.shops')}}" class="nav-link">
                                    <i class="nav-icon fa fa-circle"></i>
                                    <p>Casetas</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        @yield('header')
                    </div>
{{--                    <div class="col-sm-6">--}}
{{--                        <h1>Blank Page</h1>--}}
{{--                    </div>--}}
{{--                    <div class="col-sm-6">--}}
{{--                        <ol class="breadcrumb float-sm-right">--}}
{{--                            <li class="breadcrumb-item"><a href="#">Home</a></li>--}}
{{--                            <li class="breadcrumb-item active">Blank Page</li>--}}
{{--                        </ol>--}}
{{--                    </div>--}}
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            @yield('content')
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">

    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{URL::asset('plugins/jquery/jquery.min.js')}}"></script>
<!-- Bootstrap 4 -->
<script src="{{URL::asset('plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{URL::asset('js/adminlte.min.js')}}"></script>
<script>
    $(function(){
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("input:not(.lower), textarea:not(.lower)").on("input", function(){
            var $input = $(this);
            $input.val($input.val().toUpperCase());
        });
    });
</script>
@stack('scripts')
</body>
</html>

