@extends('layouts.admin')

@section('header')
    <h1>Sindicatos / Cooperativas <small>(Mostrar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>

            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-8">

                        <div class="form-group row">
                            <div class="col-sm-12 text-center">
                                <img width="196" class="img img-fluid" src="{{asset($model->image)}}" alt="">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('name', $model->name)}}" name="name" id="name" disabled>
                                @error('name')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Año de fundacion</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('year', $model->creation_year)}}" name="year" id="year" disabled>
                                @error('year')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="body" class="col-sm-2 col-form-label">Detalles</label>
                            <div class="col-sm-10">
                                <div style="background: #e9ecef; padding: .25rem .5rem; border-radius: .2rem; border: 1px solid #ced4da">
                                    {!! nl2br(e($model->description)) !!}
                                </div>
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
            </div>

        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')

@endpush

@push('scripts')

@endpush

