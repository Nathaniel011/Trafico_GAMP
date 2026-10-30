@extends('layouts.admin')

@section('header')
    <h1>Nuevo sindicato o cooperativa</h1>
@endsection

@section('content')
    <div class="card">
        <form method="POST" action="{{route('groups.store')}}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label for="name">Nombre</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{old('name')}}" required maxlength="255">
                            @error('name')<div class="text-danger">{{$message}}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="year">Año de fundación (opcional)</label>
                            <input type="number" id="year" name="year" class="form-control" value="{{old('year')}}" min="1900" max="{{now('America/La_Paz')->year}}">
                            @error('year')<div class="text-danger">{{$message}}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="description">Descripción (opcional)</label>
                            <textarea id="description" name="description" class="form-control" rows="5">{{old('description')}}</textarea>
                            @error('description')<div class="text-danger">{{$message}}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="image">Logotipo (opcional)</label>
                            <input type="file" id="image" name="image" class="form-control-file" accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">JPG, PNG o WEBP; máximo 10 MB.</small>
                            @error('image')<div class="text-danger">{{$message}}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{route('groups.index')}}" class="btn btn-default">Cancelar</a>
                <button type="submit" class="btn btn-primary ml-2">Guardar</button>
            </div>
        </form>
    </div>
@endsection
