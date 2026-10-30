<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso institucional · Tráfico y Vialidad</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    @vite('resources/js/institutional.js')
</head>
<body class="gamp-ui institutional-login">
<a href="#username" class="skip-link">Saltar al formulario de acceso</a>
<svg class="login-background" viewBox="0 0 1440 1000" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path fill="#ffffff" opacity=".08" d="M0 350C245 300 430 365 650 440S1110 495 1270 380S1400 350 1440 345V1000H0Z"/>
    <path fill="var(--gamp-primary-soft)" opacity=".12" d="M0 455C220 440 400 665 660 680S1130 530 1440 520V1000H0Z"/>
    <path fill="var(--gamp-primary)" opacity=".3" d="M0 725C250 635 455 620 710 685S1135 800 1440 635V1000H0Z"/>
    <path fill="var(--gamp-primary)" opacity=".45" d="M0 865C250 780 460 815 725 865S1165 900 1440 820V1000H0Z"/>
    <path fill="var(--gamp-sidebar)" opacity=".5" d="M0 1030C330 925 660 1020 900 1000S1260 1000 1440 950V1000H0Z"/>
</svg>
<main class="login-layout">
    <section class="login-card" aria-labelledby="login-brand">
        <header class="login-card-identity">
            <img class="login-logo" src="{{ asset('img/escudo.png') }}" alt="Gobierno Autónomo Municipal de Potosí" width="320" height="200" margin='0'>
            <h1 id="login-brand">Tráfico y Vialidad</h1>
            <p>Plataforma de Gestión Institucional</p>
        </header>
        <div class="login-form-panel">
            <h2 id="login-title">Ingresar</h2>
            <p>Introduce tus credenciales</p>
            <form method="POST" action="{{ route('login') }}" aria-labelledby="login-title">
                @csrf
                <div class="form-group">
                    <label class="sr-only" for="username">Usuario</label>
                    <div class="login-input">
                        <i class="far fa-user login-field-icon" aria-hidden="true"></i>
                        <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Usuario" @if($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
                    </div>
                    @error('username')<span class="invalid-feedback d-block" id="username-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="sr-only" for="password">Contraseña</label>
                    <div class="login-input password-field">
                        <i class="fas fa-lock login-field-icon" aria-hidden="true"></i>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Contraseña" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                        <button type="button" class="password-visibility" data-password-toggle aria-controls="password" aria-pressed="false">
                            <i class="far fa-eye" data-password-icon aria-hidden="true"></i>
                            <span class="sr-only" data-password-label>Mostrar contraseña</span>
                        </button>
                    </div>
                    @error('password')<span class="invalid-feedback d-block" id="password-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
