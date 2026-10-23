@extends('layouts.admin')
@section('header')<h1>Perfiles de acceso</h1>@endsection
@section('content')
<div class="card"><div class="card-body"><p>Inspector: registro de campo y avisos. Asesor Legal: consulta. Jefe de Unidad: administración y supervisión.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Usuario</th><th>Perfil actual</th><th>Asignar perfil</th></tr></thead><tbody>
@foreach($users as $user)<tr><td>{{ $user->name }}<br><small>{{ $user->username }}</small></td><td>{{ $user->roles ?: 'Consulta sin perfil asignado' }}</td><td><form method="POST" action="{{ route('user-access.update', $user) }}">@csrf<label class="sr-only" for="role-{{ $user->id }}">Perfil de {{ $user->name }}</label><select class="form-control mb-2" name="role" id="role-{{ $user->id }}" required><option value="" disabled @selected(!in_array($user->roles, ['ADMIN','JEFE_UNIDAD','INSPECTOR_NOTIFICADOR','ASESOR_LEGAL']))>Seleccione un perfil</option>@foreach(['ADMIN'=>'Administrador','JEFE_UNIDAD'=>'Jefe de Unidad','INSPECTOR_NOTIFICADOR'=>'Inspector-Notificador','ASESOR_LEGAL'=>'Asesor Legal'] as $key=>$label)<option value="{{ $key }}" @selected($user->roles === $key)>{{ $label }}</option>@endforeach</select><button class="btn btn-primary btn-sm">Guardar perfil</button></form></td></tr>@endforeach
</tbody></table></div>{{ $users->links('pagination::bootstrap-4') }}</div></div>
@endsection
