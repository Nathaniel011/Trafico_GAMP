@extends('layouts.admin')

@section('header')
    <h1>Avisos</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    @can('manage-system')<a class="btn btn-primary" href="{{route('notifications.create')}}">
                        <i class="fa fa-plus"></i>
                        Nuevo aviso
                    </a>@endcan
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive"><table class="table" id="main-table">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Titulo</th>
                    <th>Cuerpo</th>
                    <th>Nivel</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($list as $model)
                    <tr>
                        <td>{{$model->formatted_created_at}}</td>
                        <td>{{$model->title}}</td>
                        <td>{{$model->body}}</td>
                        <td>
                            <span class="badge badge-{{$model->level_class}}">{{$model->level_text}}</span>
                        </td>
                        <td>
                            <a href="{{route('notifications.show', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> Mostrar
                            </a>

                            @can('manage-system')<a href="{{route('notifications.edit', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-edit"></i> Editar
                            </a>@endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/DataTables-1.11.3/css/dataTables.bootstrap4.min.css')}}">
@endpush

@push('scripts')

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
