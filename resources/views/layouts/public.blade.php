<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Consulta los puntos de parqueo de vehículos y motocicletas y el perímetro de restricción vehicular de Potosí.">
    <title>Tráfico y Vialidad</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @vite('resources/css/public.css')
    @stack('styles')
</head>
<body id="page-top" class="institutional-landing">
    <nav class="navbar navbar-expand-lg bg-secondary text-uppercase fixed-top" id="mainNav" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand" href="#page-top">Tráfico y Vialidad</a>
            <button class="navbar-toggler text-uppercase font-weight-bold bg-primary text-white rounded" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Abrir o cerrar menú">
                Menú <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item mx-0 mx-lg-1"><a class="nav-link py-3 px-0 px-lg-3 rounded" href="#page-top">Inicio</a></li>
                    <li class="nav-item mx-0 mx-lg-1"><a class="nav-link py-3 px-0 px-lg-3 rounded" href="#parking">Parqueos</a></li>
                    <li class="nav-item mx-0 mx-lg-1"><a class="nav-link py-3 px-0 px-lg-3 rounded" href="#about">Acerca de</a></li>
                    <li class="nav-item mx-0 mx-lg-1"><a class="nav-link py-3 px-0 px-lg-3 rounded" href="#contact">Contacto</a></li>
                    <li class="nav-item mx-0 mx-lg-1">
                        @auth
                            <a class="nav-link py-3 px-0 px-lg-3 rounded" href="{{ route('home.index') }}">Ingresar</a>
                        @else
                            <a class="nav-link py-3 px-0 px-lg-3 rounded" href="{{ route('login') }}">Ingresar</a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <header class="masthead container-background bg-primary text-white text-center">
        <div class="container d-flex align-items-center flex-column">
            <img class="masthead-avatar m-0" src="{{ asset('img/escudo.png') }}" alt="Escudo del Gobierno Autónomo Municipal de Potosí" style="width: 50%;">
            <h1 class="masthead-heading text-uppercase mb-0">Tráfico y Vialidad</h1>
            <div class="divider-custom divider-light" aria-hidden="true">
                <div class="divider-custom-line"></div>
                <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
                <div class="divider-custom-line"></div>
            </div>
            <p class="masthead-subheading font-weight-light mb-0">Gobierno Autónomo Municipal de Potosí</p>
        </div>
    </header>
    <main id="contenido">@yield('content')</main>
    <footer class="footer text-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h4 class="text-uppercase mb-4">Dirección</h4>
                    <p class="lead mb-0">Avenida Sevilla S/N<br>Entre Antofagasta y Villazón</p>
                </div>
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <h4 class="text-uppercase mb-4">Gobierno Municipal</h4>
                    <a class="btn btn-outline-light" href="https://potosi.bo">Sitio web del GAMP</a>
                </div>
                <div class="col-lg-4">
                    <h4 class="text-uppercase mb-4">Tráfico y Vialidad</h4>
                    <p class="lead mb-0">Página desarrollada por Sistemas - GAMP.</p>
                </div>
            </div>
        </div>
    </footer>
    <div class="copyright py-4 text-center text-white">
        <div class="container"><small>Copyright &copy; Sistemas - GAMP</small></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    @stack('scripts')
</body>
</html>
