@extends('layouts.admin')

@section('header')
    <h1>Parqueo de Vehiculos</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
                <ul class="nav nav-pills ml-auto">
                    <a class="btn btn-primary" href="{{route('carparking.create')}}">
                        <i class="fa fa-plus"></i>
                        Nuevo Parqueo
                    </a>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div id="map" style="height: 500px;"></div>
            <table class="table" id="main-table">
                <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/DataTables-1.11.3/css/dataTables.bootstrap4.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/jQuery-3.6.0/jquery-3.6.0.min.js')}}"></script>
    <script src="{{asset('plugins/DataTables-1.11.3/js/jquery.dataTables.min.js')}}"></script>
    <script>
        $(function() {
            $('#main-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{route('carparking.data')}}",
                columns: [
                    {data: 'created_at', name: 'created_at'},
                    {data: 'name', name: 'name'},
                    {data: 'description', name: 'description'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false},
                ],
                language: {
                    url: "{{asset('plugins/DataTables-1.11.3/es.json')}}"
                }
            });
        });
    </script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap"
        async
    ></script>
    <script>

        let initialPosition = {
            lat: -19.58918, lng: -65.75348
        };

        function initMap() {

            map = new google.maps.Map(document.getElementById("map"), {
                center: initialPosition,
                zoom: 15,
                styles: [
                    {
                        featureType: "poi",
                        stylers: [
                            { visibility: "off" }
                        ]
                    }
                ]
            });
            url  = '{{ asset("/carparking/points")}}/';
            $.getJSON(url, null, function(data){
                $.each(data, function(index, p){
                    let html = "<center><h5>"+p.name+"</h5></center><center><h6>"+p.description+"</h6>Zona: "+p.zone+"<br />Espacios: "+p.quantity+"<br /></center>";
                    let infoWindow = new google.maps.InfoWindow({content: html, maxWidth: 250});
                    let xy = new google.maps.LatLng(p.lat, p.lng);
                    let image = '{{ asset("/img/car.png")}}';
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
                });
            });
        }
    </script>
@endpush
