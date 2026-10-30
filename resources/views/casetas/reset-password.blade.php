@extends('layouts.admin')
@section('header')<h1><a href="{{ route('user-access.index') }}" aria-label="Volver a perfiles de acceso"><i class="fas fa-arrow-left" aria-hidden="true"></i></a> Restablecer contraseña</h1>@endsection
@section('content')
<div class="row justify-content-center"><div class="col-12 col-lg-8 col-xl-6">
    <form method="POST" action="{{ route('user-access.reset-password',$user) }}" class="card">
        @csrf
        <div class="card-body">
            <h2 class="h5">{{ $user->name }}</h2><p>Usuario: <strong>{{ $user->username }}</strong></p>
            <p>Se generará una contraseña nueva. La anterior dejará de funcionar y se cerrarán sus otras sesiones al volver a acceder.</p>
            <p>La contraseña nueva se mostrará una sola vez para que puedas entregarla al titular. No se envía por correo.</p>
            @if(\App\Services\UserRoleRules::isAdministrator($user->roles))
            <div class="form-group"><label for="current_password">Tu contraseña actual</label><input type="password" id="current_password" name="current_password" class="form-control lower @error('current_password') is-invalid @enderror" required autocomplete="current-password" aria-describedby="reset-help"><small class="form-text text-muted" id="reset-help">Confirma tu identidad con la contraseña de tu propia cuenta.</small>@error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endif
        </div>
        <div class="card-footer d-flex flex-wrap" style="gap:10px"><button class="btn btn-primary" type="submit">Generar nueva contraseña</button><a href="{{ route('user-access.index') }}" class="btn btn-default">Cancelar</a></div>
    </form>
</div></div>
@endsection
