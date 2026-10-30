@extends('layouts.admin')
@section('header')<h1>Perfiles de acceso</h1>@endsection
@section('content')
@if(session('created_user_credentials'))
    <section class="card card-outline card-primary" aria-labelledby="credentials-title" data-created-credentials>
        <div class="card-body">
            <h2 class="h5" id="credentials-title">{{ session('credentials_title', 'Cuenta creada') }}</h2>
            <p>Entrega estos datos a la persona titular. La contraseña se muestra una sola vez; no se envía por correo.</p>
            <dl class="row mb-0">
                <dt class="col-sm-3">Usuario generado</dt><dd class="col-sm-9"><code>{{ session('created_user_credentials.username') }}</code></dd>
                <dt class="col-sm-3">Contraseña generada</dt><dd class="col-sm-9"><code style="overflow-wrap:anywhere;user-select:all">{{ session('created_user_credentials.password') }}</code></dd>
            </dl>
        </div>
    </section>
@endif
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center" style="gap:12px">
        <h2 class="h5 mb-0 flex-grow-1">Usuarios del panel</h2>
        <a href="{{ route('user-access.create') }}" class="btn btn-primary"><i class="fas fa-user-plus mr-1" aria-hidden="true"></i> Crear usuario</a>
    </div>
    <div class="card-body">
        <p>Solo Administrador y Jefe de Unidad pueden crear usuarios y asignar perfiles.</p>
        <p class="text-muted">Inspector: registro de campo y avisos. Asesor Legal: consulta. Jefe de Unidad: administración y supervisión.</p>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>Persona y usuario</th><th>CI</th><th>Correo</th><th>Perfil</th><th>Acciones</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr><td>{{ $user->name }}<br><small>{{ $user->username }}</small></td><td>{{ $user->ci ?: 'Sin registrar' }}</td><td style="overflow-wrap:anywhere">{{ $user->email ?: 'Sin registrar' }}</td><td>{{ $roles[$user->roles] ?? ($user->roles ?: 'Consulta sin perfil asignado') }}</td><td><a href="{{ route('user-access.edit', $user) }}" class="btn btn-outline-primary btn-sm" aria-label="Editar datos y perfil de {{ $user->name }}">Editar datos y perfil</a><div class="mt-2">@if($user->id === auth()->id())<a class="btn btn-outline-primary btn-sm" href="{{ route('account-password.edit') }}">Cambiar mi contraseña</a>@else<a class="btn btn-outline-primary btn-sm" href="{{ route('user-access.reset-form',$user) }}" aria-label="Restablecer contraseña de {{ $user->name }}">Restablecer contraseña</a>@endif</div></td></tr>
            @endforeach
            </tbody>
        </table></div>
        {{ $users->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
