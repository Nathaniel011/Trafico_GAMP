@extends('layouts.admin')

@section('header')
    <h1>Inspectores</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    <a class="btn btn-primary" href="{{route('inspectors.create')}}">
                        <i class="fa fa-plus"></i>
                        Nuevo inspector
                    </a>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <table class="table" id="main-table">
                <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($list as $model)
                    <tr>
                        <td>
                            {{$model->username}}
                        </td>
                        <td>{{$model->name}}</td>
                        <td>
                            @if($model->enabled)
                                <span class="badge badge-primary">ACTIVO</span>
                            @else
                                <span class="badge badge-danger">INACTIVO</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{route('inspectors.show', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> Mostrar
                            </a>

                            <a href="{{route('inspectors.edit', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/DataTables-1.11.3/css/dataTables.dataTables.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/DataTables-1.11.3/css/dataTables.bootstrap4.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/jQuery-3.6.0/jquery-3.6.0.min.js')}}"></script>
    <script src="{{asset('plugins/DataTables-1.11.3/js/jquery.dataTables.min.js')}}"></script>
    <script>
        $(function() {
            $('#main-table').DataTable({
                language: {
                    url: "{{asset('plugins/DataTables-1.11.3/es.json')}}"
                }
            });
        });
    </script>
@endpush
