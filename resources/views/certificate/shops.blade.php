@extends('layouts.admin')

@section('header')
    <h1>Casetas</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-tools">
            </div>
        </div>
        <div class="card-body">
            <div id="map" style="height: 600px;"></div>
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
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap&v=weekly"
        async
    ></script>
    <script>
        let defaultPosition = {
            lat: -19.5889181,
            lng: -65.7539971
        };

        let initialPosition = defaultPosition;

        function initMap() {
            var iconBaseImg = "{{asset('img/shop_marker.svg')}}";
            var icon = {
                url: iconBaseImg,
                size: new google.maps.Size(40, 40),
                scaledSize: new google.maps.Size(40, 40),
            };
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

            @foreach($shops as $index=>$shop)
            let marker{{$index}} = new google.maps.Marker({
                position: { lat: {{$shop->lat}}, lng:  {{$shop->lng}} },
                animation: google.maps.Animation.DROP,
                map,
                icon: icon,
                title: 'Ubicación',
            });
            marker{{$index}}.addListener('click', () => {
                let result = confirm("Quieres ver el detalle de la caseta");
                if(result){
                    window.location.replace("{{route('certificate.show', ['id'=>$shop->id])}}");
                }
            });
            @endforeach
            // const circle = new google.maps.Circle({
            //     strokeColor: "#FF0000",
            //     strokeOpacity: 0.8,
            //     strokeWeight: 2,
            //     fillColor: "#FF0000",
            //     fillOpacity: 0.35,
            //     map,
            //     center: initialPosition,
            //     radius: 25,
            // });
            //
            // map.addListener("click", (e) => {
            //     marker.setPosition({
            //         lat: e.latLng.lat(),
            //         lng: e.latLng.lng()
            //     });
            //     circle.setCenter({
            //         lat: e.latLng.lat(),
            //         lng: e.latLng.lng()
            //     });
            //     $("#lat").val(e.latLng.lat());
            //     $("#lng").val(e.latLng.lng());
            // });
        }

        /*$('#main-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{route('certificate.data')}}",
            columns: [
                {data: 'created_at', name: 'created_at'},
                {data: 'solicitante', name: 'solicitante'},
                {data: 'rubro', name: 'rubro'},
                {data: 'direccion', name: 'direccion'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            language: {
                url: "{{asset('plugins/DataTables-1.11.3/es.json')}}"
            }
        });*/
    </script>
@endpush
