@extends('layouts.admin')
@section('header')<h1>Cambiar mi contraseña</h1>@endsection
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-8 col-xl-6">
    <form action="{{ route('account-password.update') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <h2 class="h5">Seguridad de tu cuenta</h2>
            <p>Usuario: <strong>{{ auth()->user()->username }}</strong></p>
            <div class="form-group"><label for="current_password">Contraseña actual</label><input type="password" name="current_password" id="current_password" class="form-control lower @error('current_password') is-invalid @enderror" autocomplete="current-password" required>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="password">Nueva contraseña</label><input type="password" name="password" id="password" class="form-control lower @error('password') is-invalid @enderror" autocomplete="new-password" required minlength="8" maxlength="72" aria-describedby="password-help"><small id="password-help" class="form-text text-muted">Usa al menos 8 caracteres y una contraseña diferente a la actual.</small>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="password_confirmation">Confirmar nueva contraseña</label><input type="password" name="password_confirmation" id="password_confirmation" class="form-control lower" autocomplete="new-password" required minlength="8" maxlength="72"></div>
            <p class="text-muted small mb-0">Tu sesión actual continuará abierta. Las otras sesiones deberán volver a ingresar.</p>
        </div>
        <div class="card-footer d-flex flex-wrap" style="gap:10px"><button class="btn btn-primary" type="submit">Cambiar mi contraseña</button><a href="{{ route('home.index') }}" class="btn btn-default">Cancelar</a></div>
    </form>
</div></div>
@endsection
