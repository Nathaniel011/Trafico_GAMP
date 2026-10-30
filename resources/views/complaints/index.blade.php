@extends('layouts.admin')

@section('header')
    <h1>Denuncias</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">

                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive"><table class="table" id="main-table">
                <thead>
                <tr>
                    <th></th><!--IMAGEN-->
                    <th>Fecha</th>
                    <th>Placa</th>
                    <th>Notas</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($list as $model)
                    <tr>
                        <td>
                            @if(strlen($model->image)>0)
                            <img width="48" class="img img-fluid" src="{{asset($model->image)}}">
                            @endif
                        </td>
                        <td>{{$model->formatted_created_at}}</td>
                        <td>{{$model->vehicle_plaque}}</td>
                        <td>
                            {{$model->notes}}
                        </td>
                        <td>
                            @if($model->state == 'PENDIENTE')
                                <span class="badge badge-info">PENDIENTE</span>
                            @elseif($model->state == 'ACEPTADO')
                                <span class="badge " style="background-color: #764280; color: white">ACEPTADO</span>
                            @elseif($model->state == 'RECHAZADO')
                                <span class="badge badge-danger">RECHAZADO</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{route('complaints.show', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> Mostrar
                            </a>
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
