@extends('layouts.admin')

@section('header')
    <h1>Notificaciones - "{{$mode=='WAYS'? 'Vias': 'Casetas'}}" <small>(Completar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <form action="{{route('notices.update', ['id'=> $model->id,'mode'=>$mode])}}" method="POST" class="form-horizontal" enctype='multipart/form-data'>
            {{csrf_field()}}
        <div class="card-body">
            <div class="row ">
                <div class="col-md-6 col-12">

                    <div class="form-group row">
                        <label for="number" class="col-sm-2 col-form-label">Numero</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('number', $model->number)}}" name="number" id="number" disabled>
                            @error('number')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="form-group row">
                        <label for="number" class="col-sm-2 col-form-label">Fecha notificacion</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('date', $model->formatted_date)}}" name="date" id="date" disabled>
                            @error('date')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="address" class="col-sm-2 col-form-label">Direccion</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('address', $model->address)}}" name="address" id="address" disabled>
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

                    @if($photo)
                    <div class="form-group">
                        <label for="file" class="col-sm-2 col-form-label">Foto</label>
                        <div class="col-sm-10">
                            <img src="{{asset($photo->url)}}" class="img-fluid" alt="Responsive image" style="height: 220px">
                        </div>
                    </div>
                    @endif

                </div>
                <div class="col-md-6 col-12">
                    <div class="form-group row">
                        <label for="owner" class="col-sm-2 col-form-label">Propietario</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('owner', $model->owner)}}" name="owner" >
                            @error('owner')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="document" class="col-sm-2 col-form-label">Documento</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('document', $model->document)}}" name="document" >
                            @error('document')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="phone" class="col-sm-2 col-form-label">Telefono</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('phone', $model->phone)}}" name="phone" >
                            @error('phone')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="date2" class="col-sm-2 col-form-label">Fecha Aper.</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('date2', $model->date2)}}" name="date2" >
                            @error('date2')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="obs" class="col-sm-2 col-form-label">Observaciones</label>
                        <div class="col-sm-10">
                            <textarea name="obs" class="form-control" rows="4">{{old('obs', $model->obs)}}</textarea>
                            @error('obs')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="retirement" class="col-sm-2 col-form-label">Procede Retiro</label>
                        <div class="col-sm-10">
                            @php
                            $selected = old('retirement', $model->retirement);
                            if($selected == null){
                                $selected = true;
                            }
                            @endphp
                            <div class="icheck-primary d-inline">
                                <input class="option-type" type="radio" id="radioPrimary1" name="type" value="1" {{$selected===true? 'checked': ''}}>
                                <label for="radioPrimary1">SI</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="radioPrimary2" name="type" value="0" {{$selected===false? 'checked': ''}}>
                                <label for="radioPrimary2">NO</label>
                            </div>
                            @error('retirement')
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
            lat: {{old('lat', $model->lat)}},
            lng: {{old('lng', $model->lng)}}
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

        }
    </script>
@endpush

