@extends('layouts.admin')

@section('header')
    <h1><a href="{{route('certificate.index')}}"><i class="fa fa-arrow-left"></i></a> QR del solicitante: {{$model->solicitante}}</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">
            <iframe src="{{route('certificate.qr', $model->id)}}" frameborder="1" width="100%" height="800px"></iframe>
        </div>
        <div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('certificate.index')}}" class="btn btn-primary" target="_blank">
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
