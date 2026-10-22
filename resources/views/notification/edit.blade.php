@extends('layouts.admin')

@section('header')
    <h1>Notificaciones <small>(Editar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <form action="{{route('notifications.update', $model->id)}}" method="POST" class="form-horizontal">
            {{csrf_field()}}
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-8">

                        <div class="form-group row">
                            <label for="title" class="col-sm-2 col-form-label">Titulo</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('title', $model->title)}}" name="title" id="title">
                                @error('title')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="body" class="col-sm-2 col-form-label">Cuerpo</label>
                            <div class="col-sm-10">
                                <textarea id="body" name="body" rows="3" class="form-control form-control-sm lower">{{old('body', $model->body)}}</textarea>
                                @error('body')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        <div class="form-group row">
                            <label for="level" class="col-sm-2 col-form-label">Nivel</label>
                            <div class="col-sm-10">
                                @php
                                    $selected = old('level', $model->level);
                                @endphp
                                <select class="form-control-sm form-control" id="level" name="level">
                                    <option value="low" {{$selected=='low'?'selected': ''}}>Bajo - Aviso</option>
                                    <option value="medium" {{$selected=='medium'?'selected': ''}}>Medio - Tomar en cuenta</option>
                                    <option value="high" {{$selected=='high'?'selected': ''}}>High - Importante</option>
                                </select>
                                @error('level')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <a href="{{route('notifications.index')}}" class="btn btn-default">Cancelar</a>
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
@endpush

