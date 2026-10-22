@extends('layouts.admin')

@section('header')
    <h1>Parqueo de Motocicletas <small>(Editar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <form action="{{route('motoparking.update', $model->id)}}" method="POST" class="form-horizontal">
            {{csrf_field()}}
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-8">
                    <h5>Datos Básicos del Parqueo</h5>

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
                        <label for="description" class="col-sm-2 col-form-label">Ubicación</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" name="description" id="description" value="{{old('description', $model->description)}}">
                            @error('description')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="zone" class="col-sm-2 col-form-label">Zona</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" name="zone" id="zone" value="{{old('zone', $model->zone)}}">
                            @error('zone')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="quantity" class="col-sm-2 col-form-label">Espacios</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" name="quantity" id="quantity" value="{{old('quantity', $model->quantity)}}">
                            @error('quantity')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <h5>Ubicación del Parqueo</h5>

                    <div class="form-group row">
                        <input type="hidden" value="0" name="lat" id="lat">
                        <input type="hidden" value="0" name="lng" id="lng">
                        <div class="col-sm-12">
                            <div id="map" style="height: 400px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('motoparking.index')}}" class="btn btn-default">Cancelar</a>
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
    <style>
        form input[type='text']{
            text-transform: uppercase;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{asset('plugins/icheck-bootstrap/icheck.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap-datetimepicker/js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap&v=weekly"
        async
    ></script>
    <script>

        let initialPosition = {
            lat: {{old('lat', $model->lat)}}, lng: {{old('lng', $model->lng)}}
        };

        function initMap() {
            $("#lat").val(initialPosition.lat);
            $("#lng").val(initialPosition.lng);

            map = new google.maps.Map(document.getElementById("map"), {
                center: initialPosition,
                zoom: 17.75,
            });

            let marker = new google.maps.Marker({
                position: initialPosition,
                map,
                title: 'Ubicación',
                icon: '{{ asset("/img/moto.png")}}',
            });

            const circle = new google.maps.Circle({
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
                map,
                center: initialPosition,
                radius: 15,
            });

            url  = '{{ asset("/motoparking/points")}}/';
            $.getJSON(url, null, function(data){
                $.each(data, function(index, p){
                    if({{$model->id}}!=p.id){
                        let html = "<center><h5>"+p.name+"</h5></center><center><h6>"+p.description+"</h6>Zona: "+p.zone+"<br />Espacios: "+p.quantity+"<br /></center>";
                        let infoWindow = new google.maps.InfoWindow({content: html, maxWidth: 250});
                        let xy = new google.maps.LatLng(p.lat, p.lng); 
                        let image = '{{ asset("/img/moto.png")}}';
                        let marker = new google.maps.Marker({
                            position: xy,
                            draggable: false,
                            map,
                            animation: google.maps.Animation.DROP,
                            title: 'Parqueo '+p.name,
                            icon: image,
                        });   
                        google.maps.event.addListener(marker, 'click', function() {
                        infoWindow.open(map, marker);
                        });
                        google.maps.event.addListener(map, "click", function() {
                        infoWindow.close();
                        });
                        google.maps.event.addListener(marker, 'mouseover', function () {
                        infoWindow.open(map, marker);
                        });
                        google.maps.event.addListener(marker, 'mouseout', function () {
                        infoWindow.close();
                        });
                    }
                });
            });

            map.addListener("click", (e) => {
                marker.setPosition({
                    lat: e.latLng.lat(),
                    lng: e.latLng.lng()
                });
                circle.setCenter({
                    lat: e.latLng.lat(),
                    lng: e.latLng.lng()
                });
                $("#lat").val(e.latLng.lat());
                $("#lng").val(e.latLng.lng());
            });
        }
    </script>
@endpush

