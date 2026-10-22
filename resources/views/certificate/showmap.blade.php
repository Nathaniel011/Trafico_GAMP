@extends('layouts.admin')

@section('header')
    <h1><a href="{{route('certificate.show',['id' => $model->id])}}"><i class="fa fa-arrow-left"></i></a> Datos de la Caseta :</h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <div class="card-body">
            
            <div class="row justify-content-center">
                <div class="col-8 text-center">
                    <div id="pmap">
                        <div class='print_only'>
                        <br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
                        </div>
                        <center>
                        <h5>CROQUIS DE UBICACIÓN. SOLICITANTE: <b> {{$model->solicitante}} </b></h5>
                        <div id="map" style="width: 700px; height: 480px;"></div>
                        </center>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="javascript:printmap(pmap);">Imprimir</button>
                </div>
            </div>
        </div>
        <div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('certificate.index')}}" class="btn btn-primary" target="_blank">
                <i class="fa fa-arrow-left"></i>
                Volver a Listado
            </a>
        </div>
        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')
    <style>
        .print_only{
            display:none;
        }
        @media print {
            .print-only{
                display: block;
            }
        }
    </style>
@endpush

@push('scripts')
<script src="{{asset('js/jquery.PrintArea.js')}}"></script>
<script>
    function printmap(pmap){
        $('#pmap').printArea({mode: 'iframe', popClose: false});
        // var printContents = document.getElementById('pmap').innerHTML;
        // w = window.open();
        // w.document.write(printContents);
        // w.document.close(); // necessary for IE >= 10
        // w.focus(); // necessary for IE >= 10
		// w.print();
		// w.close();
        return true;
    }
</script>

<script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap&v=weekly"
        async
></script>
<script>
    let initialPosition = {
        lat: {{old('lat', $model->lat)}}, lng: {{old('lng', $model->lng)}}
    };

    function initMap() {

        map = new google.maps.Map(document.getElementById("map"), {
            center: initialPosition,
            zoom: 18,
            disableDefaultUI: true,
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
    }
</script>

@endpush
