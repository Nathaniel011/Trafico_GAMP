@extends('layouts.admin')

@section('header')
    <h1>Sindicatos / Cooperativas <small>(Editar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
            <form action="{{route('groups.update', $model->id)}}" method="POST" class="form-horizontal" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-8">

                        <div class="form-group row">
                            <label for="image" class="col-sm-2 col-form-label">Logo</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control form-control-sm" name="image" id="image">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('name', $model->name)}}" name="name" id="name">
                                @error('name')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Año de fundacion</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control form-control-sm" value="{{old('year', $model->creation_year)}}" name="year" id="year">
                                @error('year')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="description" class="col-sm-2 col-form-label">Detalles</label>
                            <div class="col-sm-10">
                                <textarea id="description" name="description" rows="3" class="form-control form-control-sm">{{old('description', $model->description)}}</textarea>
                                @error('details')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <a href="{{route('groups.index')}}" class="btn btn-default">Volver</a>
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
    <link rel="stylesheet" href="{{asset('plugins/summernote/summernote-bs4.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/summernote/summernote-bs4.min.js')}}"></script>
    <script src="{{asset('plugins/summernote/lang/summernote-es-ES.min.js')}}"></script>
    <script>
        $(function(){
            $('#description').summernote({
                lang: 'es-ES'
            });
        });
    </script>
@endpush

