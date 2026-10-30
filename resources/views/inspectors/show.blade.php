@extends('layouts.admin')

@section('header')
    <h1>Inspectores <small>(Detalle)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{$model->name}}</dd>
                <dt class="col-sm-3">Usuario</dt>
                <dd class="col-sm-9">{{$model->username}}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">{{$model->enabled ? 'Activo' : 'Inactivo'}}</dd>
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{route('inspectors.index')}}" class="btn btn-default">Volver</a>
            @can('manage-system')
                <a href="{{route('inspectors.edit', $model->id)}}" class="btn btn-primary ml-2">
                    <i class="fa fa-edit" aria-hidden="true"></i> Editar
                </a>
            @endcan
        </div>
    </div>
@endsection
