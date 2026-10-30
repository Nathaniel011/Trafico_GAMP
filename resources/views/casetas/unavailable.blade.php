@extends('layouts.admin')
@section('header')<h1>Control de casetas no disponible</h1>@endsection
@section('content')
<div class="card"><div class="card-body"><p>{{ $message }}</p>
<a class="btn btn-primary" href="{{ route('casetas.index') }}">Consultar documentos históricos</a>
<a class="btn btn-outline-primary" href="{{ route('notices.index', ['mode'=>'SHOP']) }}">Notificaciones</a>
</div></div>
@endsection
