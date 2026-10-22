@extends('layouts.admin')

@section('header')
    <h1>Inspectores <small>(Editar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">

        </div>
        <form method="POST" class="form-horizontal" action="{{route('inspectors.update', $model->id)}}">
        {{csrf_field()}}
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-8">

                    <div class="form-group row">
                        <label for="title" class="col-sm-2 col-form-label">Nombre</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('name', $model->name)}}" name="name" id="name">
                            @error('name')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="title" class="col-sm-2 col-form-label">Usuario</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm lower" value="{{old('username', $model->username)}}" name="username" id="username">
                            @error('username')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="title" class="col-sm-2 col-form-label">Password</label>
                        <div class="col-sm-5">
                            <input type="password" class="form-control form-control-sm lower" name="password" id="password" placeholder="Password">
                            @error('password')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-5">
                            <input type="password" class="form-control form-control-sm lower" name="password_confirmation" id="password_confirmation" placeholder="Confirmar password">
                            @error('password_confirmation')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="title" class="col-sm-2 col-form-label">Activo</label>
                        <div class="col-sm-10">
                            @php
                            $enabled = old('enabled', $model->enabled);
                            @endphp
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" class="form-check-input" value="1" name="enabled" id="enabled" {{$enabled?'checked': ''}}>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('inspectors.index')}}" class="btn btn-default">Cancelar</a>
            <button type="submit" class="btn btn-primary ml-2">
                Guardar
            </button>
        </div>
        </form>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->
@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/bootstrap-datetimepicker/css/tempusdominus-bootstrap-4.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/icheck-bootstrap/icheck.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap-datetimepicker/js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script>
        $(function(){
            $(".custom-checkbox").iCheck({
                checkboxClass: 'icheckbox_square-blue',
            });
        });
    </script>
@endpush

