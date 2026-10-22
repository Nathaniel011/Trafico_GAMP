@extends('layouts.admin')

@section('header')
    <h1>Notificaciones</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    <a class="btn btn-primary" href="{{route('notices.create', ['mode'=>$mode])}}">
                        <i class="fa fa-plus"></i>
                        Nueva notificacion
                    </a>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <table class="table" id="main-table">
                <thead>
                <tr>
                    <th>Nro. Not.</th>
                    <th>Fecha</th>
                    <th>Propietario</th>
                    <th>Direccion</th>
                    <th>Plazo</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($list as $model)
                    <tr>
                        <td>{{$model->number}}</td>
                        <td>{{$model->formatted_date}}</td>
                        <td>{{$model->owner??'...'}}</td>
                        <td>{{$model->address}}</td>
                        <td>
                            @php
                            $now = \Carbon\Carbon::now();
                            $notice = new \Carbon\Carbon($model->notice_date);
                            $temp = (int) $now->diffInDays($notice, true);
                            @endphp
                            @if($temp > 2)
                                <span class="badge badge-danger">VENCIDO</span>
                            @elseif($temp > 1)
                                <span class="badge badge-warning">PENDIENTE</span>
                            @else
                                <span class="badge badge-success">NOTIFICADO</span>
                            @endif
                        </td>
                        <td>
                            @if($model->retirement === null)
                            <span class="badge badge-info">PENDIENTE</span>
                            @elseif($model->retirement===false)
                                <span class="badge badge-primary">SIN RETIRO</span>
                            @elseif($model->retirement===true)
                                <span class="badge badge-danger">RETIRO</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{route('notices.show', $model->id)}}" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> Mostrar
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
