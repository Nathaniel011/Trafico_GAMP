@extends('layouts.admin')
@section('header')<h1>Ubicación de la inspección</h1>@endsection
@section('content')
<div class="card"><div class="card-body"><p>{{ $model->direccion }}</p>@include('casetas.location', ['location'=>$model, 'readOnly'=>true])<a href="{{ route('certificate.show', $model) }}">Volver al certificado</a></div></div>
@endsection
