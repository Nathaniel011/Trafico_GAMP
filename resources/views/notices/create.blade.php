@extends('layouts.admin')

@section('header')
    <h1>Notificaciones - "{{$mode=='WAYS'? 'Vias': 'Casetas'}}" <small>(Nuevo)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <form action="{{route('notices.store', ['mode'=>$mode])}}" method="POST" class="form-horizontal" enctype='multipart/form-data'>
            {{csrf_field()}}
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-md-6 col-12">

                    <div class="form-group row">
                        <label for="number" class="col-sm-2 col-form-label">Numero</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('number', $model->number)}}" name="number" id="number">
                            @error('number')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="form-group row">
                        <label for="number" class="col-sm-2 col-form-label">Fecha notificacion</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('date', date('d/m/Y H:i'))}}" name="date" id="date" readonly>
                            @error('date')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="address" class="col-sm-2 col-form-label">Direccion</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('address', $model->address)}}" name="address" id="address">
                            @error('address')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <input type="hidden" value="0" name="lat" id="lat">
                        <input type="hidden" value="0" name="lng" id="lng">
                        <div class="col-sm-12">
                            <div id="map" style="height: 400px;"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="file" class="col-sm-2 col-form-label">Foto</label>
                        <div class="col-sm-10">
                            <input type="file" class="form-control form-control-sm" value="{{old('file')}}" name="file" id="file">
                            @error('file')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('notices.index', ['mode'=>$mode])}}" class="btn btn-default">Cancelar</a>
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
{{--    <link rel="stylesheet" href="{{asset('plugins/uppy/uppy.min.css')}}">--}}
@endpush

@push('scripts')
    <script src="{{asset('plugins/icheck-bootstrap/icheck.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap-datetimepicker/js/tempusdominus-bootstrap-4.min.js')}}"></script>
{{--    <script src="{{asset('plugins/uppy/uppy.min.js')}}"></script>--}}
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap&v=weekly"
        async
    ></script>
    <script>
        initializeLocation();

        function initializeLocation(){

        }


        function setCoordinates(coords){

        }

        let initialPosition = {
            lat: {{old('lat', -19.581833)}},
            lng: {{old('lng', -65.755791)}}
        };





        function initMap() {
            $("#lat").val(initialPosition.lat);
            $("#lng").val(initialPosition.lng);

            map = new google.maps.Map(document.getElementById("map"), {
                center: initialPosition,
                zoom: 17.75,
                styles: [
                    {
                        featureType: "poi",
                        stylers: [
                            { visibility: "off" }
                        ]
                    }
                ]
            });



            let marker = new google.maps.Marker({
                position: initialPosition,
                map,
                title: 'Ubicación'
            });

            const circle = new google.maps.Circle({
                strokeColor: "#FF0000",
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: "#FF0000",
                fillOpacity: 0.35,
                map,
                center: initialPosition,
                radius: 25,
            });

            if ("geolocation" in navigator) {
                const watchID = navigator.geolocation.watchPosition((position) => {
                    const coords = position.coords;
                    marker.setPosition({
                        lat: coords.latitude,
                        lng: coords.longitude
                    });
                    circle.setCenter({
                        lat: coords.latitude,
                        lng: coords.longitude
                    });
                    map.setCenter({
                        lat: coords.latitude,
                        lng: coords.longitude
                    });
                    $("#lat").val(coords.latitude);
                    $("#lng").val(coords.longitude);
                }, ()=>{}, {
                    enableHighAccuracy: true,
                    maximumAge: 30000,
                    timeout: 27000,
                    });
            } else {
            /* geolocation IS NOT available */
            }

        }
    </script>
@endpush

