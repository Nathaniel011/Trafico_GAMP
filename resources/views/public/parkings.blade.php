@extends('layouts.public')
@section('content')
@include('parking.assets')
<section class="page-section text-center" id="parking" data-parking-map data-points-url="{{ url('public/carpoints') }}" data-kind="car" data-fit-bounds="1" data-initial-layer="{{ request('vista') === 'perimetro' ? 'perimeter' : 'car' }}" data-car-icon="{{ asset('img/car.png') }}" data-moto-icon="{{ asset('img/moto.png') }}" aria-labelledby="map_title">
    <div class="container">
        <h2 class="page-section-heading text-uppercase text-secondary h2" id="map_title" data-parking-title>PUNTOS DE PARQUEO · VEHÍCULOS</h2>
        <div class="divider-custom" aria-hidden="true">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
            <div class="divider-custom-line"></div>
        </div>
        <div class="field-actions justify-content-center my-3" role="group" aria-label="Capas del mapa">
            <button type="button" class="btn btn-primary" id="bcar" data-parking-kind="car" data-url="{{ url('public/carpoints') }}" data-title="PUNTOS DE PARQUEO · VEHÍCULOS" aria-pressed="true">VEHÍCULOS</button>
            <button type="button" class="btn btn-primary" id="bmoto" data-parking-kind="moto" data-url="{{ url('public/motopoints') }}" data-title="PUNTOS DE PARQUEO · MOTOCICLETAS" aria-pressed="false">MOTOCICLETAS</button>
            <button type="button" class="btn btn-primary" id="bperimeter" data-perimeter data-url="{{ asset('maps/restriccion-vehicular.geojson') }}" aria-pressed="false">PERÍMETRO</button>
            <button type="button" class="btn btn-outline-primary" data-user-location><i class="fas fa-location-arrow me-1" aria-hidden="true"></i> Mi ubicación</button>
        </div>
        <p data-parking-status role="status" aria-live="polite">Consultando puntos de parqueo…</p>
        <div id="map" class="control-map" data-parking-canvas aria-label="Mapa público de parqueos y restricción vehicular"></div>
        <div class="field-actions justify-content-center mt-3">
            <span>Selecciona un punto para consultar sus datos.</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-parking-retry><i class="fas fa-sync-alt me-1" aria-hidden="true"></i> Actualizar mapa</button>
        </div>
    </div>
</section>
<section class="page-section bg-primary text-white mb-0" id="about" aria-labelledby="about-title">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-white" id="about-title">Acerca de Tráfico y Vialidad</h2>
        <div class="divider-custom divider-light" aria-hidden="true">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
            <div class="divider-custom-line"></div>
        </div>
        <div class="row">
            <div class="col-lg-4 ms-auto">
                <h3>Información vial</h3>
                <p class="lead">El mapa permite consultar la ubicación, zona y espacios registrados de cada parqueo. Puedes alternar entre vehículos y motocicletas o usar tu ubicación para orientarte.</p>
            </div>
            <div class="col-lg-4 me-auto">
                <h3>Atención municipal</h3>
                <p class="lead">Las consultas y gestiones municipales corresponden a la Jefatura de Tráfico y Vialidad del Gobierno Autónomo Municipal de Potosí. El acceso institucional está reservado al personal con una cuenta habilitada.</p>
            </div>
        </div>
    </div>
</section>
<section class="page-section" id="contact" aria-labelledby="contact-title">
    <div class="container">
        <h2 class="page-section-heading text-center text-uppercase text-secondary mb-0" id="contact-title">Contáctate</h2>
        <div class="divider-custom" aria-hidden="true">
            <div class="divider-custom-line"></div>
            <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
            <div class="divider-custom-line"></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7 text-center">
                <p class="lead">Para consultas y trámites, acude a la Jefatura de Tráfico y Vialidad del Gobierno Autónomo Municipal de Potosí.</p>
                <p class="lead">Avenida Sevilla S/N<br>Entre Antofagasta y Villazón</p>
                <a class="btn btn-primary btn-xl" href="https://potosi.bo">Visitar sitio municipal</a>
            </div>
        </div>
    </div>
</section>
@endsection
