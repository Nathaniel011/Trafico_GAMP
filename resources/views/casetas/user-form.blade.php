@extends('layouts.admin')
@section('header')
<h1><a href="{{ route('user-access.index') }}" aria-label="Volver a perfiles de acceso"><i class="fas fa-arrow-left" aria-hidden="true"></i></a> {{ $user->exists ? 'Editar usuario' : 'Crear usuario' }}</h1>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-12 col-xl-9">
    <form method="POST" action="{{ $user->exists ? route('user-access.update', $user) : route('user-access.store') }}" class="card">
        @csrf
        <div class="card-body">
            <h2 class="h5">Datos de la persona</h2>
            <p class="text-muted">Completa nombre, CI y correo y selecciona su perfil de acceso.</p>
            <div class="form-group"><label for="name">Nombre completo</label><input id="name" name="name" value="{{ old('name', $user->name) }}" type="text" class="form-control lower @error('name') is-invalid @enderror" required maxlength="255" autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="row">
                <div class="col-md-5"><div class="form-group"><label for="ci">CI</label><input id="ci" name="ci" value="{{ old('ci', $user->ci) }}" type="text" class="form-control lower @error('ci') is-invalid @enderror" required maxlength="80" autocomplete="off"><small class="form-text text-muted">Incluye complemento o extensión si corresponde.</small>@error('ci')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
                <div class="col-md-7"><div class="form-group"><label for="email">Correo electrónico</label><input id="email" name="email" value="{{ old('email', $user->email) }}" type="email" class="form-control lower @error('email') is-invalid @enderror" required maxlength="254" autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            </div>
            <div class="form-group"><label for="role">Perfil de acceso</label><select id="role" name="role" class="form-control @error('role') is-invalid @enderror" required><option value="" disabled @selected(!old('role', $user->roles))>Selecciona un perfil</option>@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected(old('role', $user->roles)===$value)>{{ $label }}</option>@endforeach</select><small class="form-text text-muted">Solo puede existir una cuenta Administrador.</small>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="card-footer d-flex flex-wrap" style="gap:10px"><button type="submit" class="btn btn-primary">{{ $user->exists ? 'Guardar datos y perfil' : 'Crear usuario' }}</button><a href="{{ route('user-access.index') }}" class="btn btn-default">Cancelar</a></div>
    </form>
</div></div>
@endsection
