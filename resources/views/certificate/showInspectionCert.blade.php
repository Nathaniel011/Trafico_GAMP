@extends('layouts.admin')

@section('header')
    <h1><a href="{{route('certificate.index')}}"><i class="fa fa-arrow-left"></i></a> Certificaciones...</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">

            <form method="GET" action="">
                <input type="hidden" name="id" value="{{ $model->id }}">
                <div class="row">
                    <div class="col-md-4">
                        <label><b>PARA - Nombre</b></label>
                        <input type="text" name="para_nombre" class="form-control lower" value="{{ request('para_nombre', 'Ing. Alvaro Julio Duran Arismendi') }}">
                    </div>
                    <div class="col-md-4">
                        <label><b>PARA - Cargo</b></label>
                        <input type="text" name="para_rol" class="form-control" value="{{ request('para_rol', 'JEFE DE TRÁFICO Y VIALIDAD – G.A.M.P.') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label><b>VÍA - Nombre</b></label>
                        <input type="text" name="via_nombre" class="form-control lower" value="{{ request('via_nombre', 'Abg. Jessica Sariel Arando Coro') }}">
                    </div>
                    <div class="col-md-4">
                        <label><b>VÍA - Cargo</b></label>
                        <input type="text" name="via_rol" class="form-control" value="{{ request('via_rol', 'ASESOR JURIDICO DE TRÁFICO Y VIALIDAD – G.A.M.P.') }}">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label><b>DE - Nombre</b></label>
                        <input type="text" name="de_nombre" class="form-control lower" value="{{ request('de_nombre', 'Tec. Boris Fernando Montesinos Valda') }}">
                    </div>
                    <div class="col-md-4">
                        <label><b>DE - Cargo</b></label>
                        <input type="text" name="de_rol" class="form-control" value="{{ request('de_rol', 'NOTIFICADOR - TRÁFICO Y VIALIDAD') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success" type="submit">
                        <i class="fa fa-file-pdf-o"></i> Generar PDF
                    </button>
                </div>
            </form>

        <hr>

        @if(request()->has('para_nombre'))
            <iframe
                src="{{ route('certificate.pdf.inspectionCert', [
                    'id' => $model->id,
                    'para_nombre' => request('para_nombre'),
                    'para_rol' => request('para_rol'),
                    'via_nombre' => request('via_nombre'),
                    'via_rol' => request('via_rol'),
                    'de_nombre' => request('de_nombre'),
                    'de_rol' => request('de_rol'),
                ]) }}"
                title="Informe de certificación de inspección PDF"
                frameborder="1"
                width="100%"
                height="800px">
            </iframe>
        @endif
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('certificate.index')}}" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i>
                Volver a
            </a>
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')

@endpush

@push('scripts')

@endpush
