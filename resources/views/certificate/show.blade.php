@extends('layouts.admin')
@section('header')
<h1><a href="{{ route('certificate.index') }}"><i class="fa fa-arrow-left"></i></a> Certificaciones</h1>
@endsection
@section('content')
@php($hasTechnicalData = $model->inspection_date !== null)
@if($hasTechnicalData)
@include('casetas.assets')
<div class="card" data-certificate-capture data-lat="{{ $model->lat }}" data-lng="{{ $model->lng }}" data-upload="{{ route('certificate.map-capture', $model->id) }}" data-version="{{ app(\App\Services\CertificateMapCapture::class)->version($model) }}" data-token="{{ csrf_token() }}" data-pdf="{{ route('certificate.pdf', $model->id) }}">
    <div class="card-body">
        <div class="field-actions mb-3">
            <a href="{{ route('certificate.showmap', $model->id) }}" class="btn btn-primary">Ubicación en mapa</a>
            <button type="button" class="btn btn-outline-primary" data-capture-retry>Actualizar croquis</button>
        </div>
        <p role="status" aria-live="polite" data-capture-status>Preparando el croquis con Leaflet y OpenStreetMap…</p>
        <div style="max-width:100%;overflow:auto" data-capture-preview>
            <div data-capture-map style="width:640px;height:400px;"></div>
        </div>
        <iframe data-certificate-pdf title="Certificado PDF" src="{{ route('certificate.pdf', $model->id) }}" style="border:1px solid #dce3e8;width:100%;height:800px"></iframe>
    </div>
    <div class="card-footer"><a class="btn btn-primary" href="{{ route('certificate.index') }}">Volver a certificaciones</a></div>
</div>
@else
<div class="card"><div class="card-body"><h2 class="h5">Determinación legal</h2><iframe title="Informe legal PDF" src="{{ route('certificate.pdf.legal', $model->id) }}" style="width:100%;height:800px;border:1px solid #dce3e8"></iframe></div></div>
@endif
@endsection
@push('scripts')
@vite('resources/js/certificate-capture.js')
@endpush
