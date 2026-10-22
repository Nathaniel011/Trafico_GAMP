@extends('layouts.admin')

@section('header')
    <h1>Certificación de inspección <small>(Nuevo)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>
        <form action="{{route('certificate.store')}}" method="POST" class="form-horizontal">
            {{csrf_field()}}
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-8">
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Fecha inspeccción</label>
                        <div class="col-sm-4">
                            <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" value="{{old('inspection_date')}}" data-target="#datetimepicker1" name="inspection_date"/>
                                <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                            @error('inspection_date')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <h5>Ocupacion de via</h5>
                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Tipo</label>
                        <div class="col-sm-10">
                            <div class="icheck-primary d-inline">
                                <input class="option-type" type="radio" id="radioPrimary1" name="type" value="CASETA" {{old('type', $model->tipo)=='CASETA'? 'checked': ''}}>
                                <label for="radioPrimary1">CASETA</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="radioPrimary2" name="type" value="KIOSKO" {{old('type', $model->tipo)=='KIOSKO'? 'checked': ''}}>
                                <label for="radioPrimary2">KIOSKO</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="radioPrimary3" name="type" value="ANAQUEL" {{old('type', $model->tipo)=='ANAQUEL'? 'checked': ''}}>
                                <label for="radioPrimary3">ANAQUEL</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="another_option" name="type" value="OTROS" {{(!in_array(old('type', $model->tipo), array("CASETA","KIOSKO","ANAQUEL"))&&old('type', $model->tipo)!="")? 'checked': ''}}>
                                <label for="another_option">OTRO</label>
                            </div>

                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 col-form-label">Modalidad</label>
                        <div class="col-sm-10">
                            <div class="icheck-primary d-inline">
                                <input class="option-type" type="radio" id="radioPrimary4" name="mode" value="NUEVO" {{old('mode', $model->modalidad)=='NUEVO'? 'checked': ''}}>
                                <label for="radioPrimary4">NUEVO</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="radioPrimary5" name="mode" value="RENOVACION" {{old('mode', $model->modalidad)=='RENOVACION'? 'checked': ''}}>
                                <label for="radioPrimary5">RENOVACIÓN</label>
                            </div>

                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="radioPrimary6" name="mode" value="REGULARIZACION" {{old('mode', $model->modalidad)=='REGULARIZACION'? 'checked': ''}}>
                                <label for="radioPrimary6">REGULARIZACIÓN</label>
                            </div>

                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-2 small">(Especifique Otro)</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('another_type', $model->another_type)}}" name="another_type" id="another_type" placeholder="OTRO TIPO">
                            @error('another_type')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="solicitante" class="col-sm-2 col-form-label">Solicitante</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('solicitante', $model->solicitante)}}" name="solicitante" id="solicitante">
                            @error('solicitante')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="ci" class="col-sm-2 col-form-label">Cédula de Identidad</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('ci', $model->ci)}}" name="ci" id="ci">
                            @error('ci')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="celular" class="col-sm-2 col-form-label">Celular</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" value="{{old('celular', $model->celular)}}" name="celular" id="celular">
                            @error('celular')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <div class="form-group row">
                        <label for="rubro" class="col-sm-2 col-form-label">Rubro</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" name="rubro" id="rubro" value="{{old('rubro', $model->rubro)}}">
                            @error('rubro')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5>Ubicación</h5>

                    <div class="form-group row">
                        <label for="direccion" class="col-sm-2 col-form-label">Calle o Av.</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control form-control-sm" name="direccion" id="direccion" value="{{old('direccion', $model->direccion)}}">
                            @error('direccion')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <h5>Características</h5>

                    <div class="form-group row">
                        <div class="col-sm-5 row">
                            <label class="col-sm-4 col-form-label">Color</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control-sm form-control" name="color" value="{{old('color', $model->color)}}">
                                @error('color')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-2"></div>

                        <div class="col-sm-5 row">
                            <label class="col-sm-4 col-form-label">Material</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control-sm form-control" name="material" value="{{old('material', $model->material)}}">
                                @error('material')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-5 row">
                            <label class="col-sm-4 col-form-label">Ancho</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control-sm form-control" name="dim_ancho" id="dim_ancho" value="{{old('dim_ancho', $model->dim_ancho)}}">
                                @error('dim_ancho')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-2"></div>

                        <div class="col-sm-5 row">
                            <label class="col-sm-4 col-form-label">Largo</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control-sm form-control" name="dim_largo" value="{{old('dim_largo', $model->dim_largo)}}">
                                @error('dim_largo')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h5>Dimensión de vía peatonal</h5>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-3 col-form-label">Tipo Vía Peatonal</label>
                        <div class="col-sm-3">
                            <select name="tipo_via" class="form-control form-control-sm">
                                <option value="ACERA" {{old('tipo_via', $model->tipo_via)=='ACERA'?'selected': ''}}>ACERA</option>
                                <option value="CALZADA" {{old('tipo_via', $model->tipo_via)=='CALZADA'?'selected': ''}}>CALZADA</option>
                                <option value="JARDINERA" {{old('tipo_via', $model->tipo_via)=='JARDINERA'?'selected': ''}}>JARDINERA</option>
                                <option value="OTRO" {{old('tipo_via', $model->tipo_via)=='OTRO'?'selected': ''}}>OTRO</option>
                            </select>
                            @error('tipo_via')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-3 col-form-label">Dimensión Vía Peatonal</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control-sm form-control" name="dim_via" id="dim_via" value="{{old('dim_via', $model->dim_via)}}">
                            @error('dim_via')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-3 col-form-label">% Ocupado</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control-sm form-control" readonly name="dim_perc_ocupado" id="dim_perc_ocupado" value="{{old('dim_perc_ocupado', $model->dim_perc_ocupado)}}">
                            @error('dim_perc_ocupado')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <h5>Referencia edil</h5>



                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-4 col-form-label">Producto de venta o servicio</label>
                        <div class="col-sm-6">
                            <select name="ref_product" id="ref_product" class="form-control form-control-sm">
                                <option disabled selected value>  </option>
                                @foreach ($model->products as $product)
                                    <option value="{{$product}}" {{in_array(old('ref_product', $model->ref_product),['HAMBURGUESAS', 'RELLENOS', 'SALTEÑAS Y TUCUMANAS', 'VENTA DE DULCES', 'REFRIGERIOS', 'COPIA DE LLAVES', 'CASETA DE SEGURIDAD', 'REFACCION DE ZAPATOS'])?(old('ref_product', $model->ref_product)==$product?'selected': ''):(($product=='OTROS (DESCRIBIR)'&&old('ref_product', $model->ref_product)!='')?'selected':'')}}>{{$product}}</option>
                                @endforeach
                            </select>
                            @error('ref_product')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-4 col-form-label small">(Especifique Otro)</label>
                        <div class="col-sm-6">
                            <input type="text" class="form-control form-control-sm" value="{{old('ref_other_product', $model->ref_other_product)}}" name="ref_other_product" id="ref_other_product" placeholder="OTRO PRODUCTO">
                            @error('ref_other_product')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-4 col-form-label">Turno</label>
                        <div class="col-sm-6">
                            <select id="ref_turn" name="ref_turn" class="form-control form-control-sm">
                                <option disabled selected value> </option>
                                <option value="DIURNO" {{old('ref_turn', $model->ref_turn)=='DIURNO'?'selected': ''}}>DIURNO</option>
                                <option value="NOCTURNO" {{old('ref_turn', $model->ref_turn)=='NOCTURNO'?'selected': ''}}>NOCTURNO</option>
                                <option value="TRASNOCHE" {{old('ref_turn', $model->ref_turn)=='TRASNOCHE'?'selected': ''}}>TRASNOCHE</option>
                            </select>
                            @error('ref_turn')
                            <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-12 row justify-content-center">
                        <label class="col-sm-4 col-form-label">Horario</label>
                        <div class="col-sm-6 row no-gutters">
                            <div class="col-sm-2" style="text-align: right; padding-right: 8px;">
                                De
                            </div>
                            <div class="col-sm-4">
                                <input id="ref_start" type="text" name="ref_start" data-toggle="datetimepicker" class="datetimepicker-input form-control-sm form-control time-selector" value="{{old('ref_start', $model->ref_start)}}">
                                @error('ref_start')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-2" style="text-align: right; padding-right: 8px;">
                                A
                            </div>
                            <div class="col-sm-4">
                                <input id="ref_end" type="text" name="ref_end" data-toggle="datetimepicker" class=" datetimepicker-input form-control-sm form-control time-selector" value="{{old('ref_end', $model->ref_end)}}">
                                @error('ref_end')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <h5>Croquis de ubicación</h5>

                    <div class="form-group row">
                        <input type="hidden" value="0" name="lat" id="lat">
                        <input type="hidden" value="0" name="lng" id="lng">
                        <div class="col-sm-12">
                            <div id="map" style="height: 400px;"></div>
                        </div>
                    </div>

                    <h5>Determinación informe técnico</h5>
                    <div class="form-group row justify-content-center">
                        <div class="col-sm-4">
                            <div class="icheck-primary d-inline">
                                <input class="option-type" type="radio" id="proceed_technical_yes" name="technical_proceed" value="1" {{old('technical_proceed', $model->technical_proceed)? 'checked': ''}}>
                                <label for="proceed_technical_yes">PROCEDENTE</label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="proceed_technical_no" name="technical_proceed" value="0" {{old('technical_proceed', $model->technical_proceed)? '': 'checked'}}>
                                <label for="proceed_technical_no">IMPROCEDENTE</label>
                            </div>
                        </div>
                    </div>

                    <h5>Determinación informe legal</h5>
                    <div class="form-group row justify-content-center">
                        <div class="col-sm-4">
                            <div class="icheck-primary d-inline">
                                <input class="option-type" type="radio" id="proceed_legal_yes" name="legal_proceed" value="1" {{old('legal_proceed', $model->legal_proceed)? 'checked': ''}}>
                                <label for="proceed_legal_yes">PROCEDENTE</label>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="icheck-primary d-inline ml-4">
                                <input class="option-type" type="radio" id="proceed_legal_no" name="legal_proceed" value="0" {{old('legal_proceed', $model->legal_proceed)? '': 'checked'}}>
                                <label for="proceed_legal_no">IMPROCEDENTE</label>
                            </div>
                        </div>
                    </div>

                    <h5>Observaciones adicionales</h5>
                    <div class="form-group row justify-content-center">
                        <div class="col-sm-12">
                            <textarea name="conclusions" class="form-control" rows="5">{{old('conclusions', '')}}</textarea>
                            @error('conclusions')
                                <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
            <a href="{{route('certificate.index')}}" class="btn btn-default">Cancelar</a>
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
    <script>
        $("#dim_via").change(function(){
            $("#dim_perc_ocupado").val(parseFloat((parseFloat($("#dim_ancho").val())*100)/parseFloat($("#dim_via").val())).toFixed(2));
        });
    </script>
    <script src="{{asset('plugins/icheck-bootstrap/icheck.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap-datetimepicker/js/tempusdominus-bootstrap-4.min.js')}}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap&v=weekly"
        async
    ></script>
    <script>
        $(document).ready(function(){
            $("#table-tipos tbody tr").on("click", function (e){
                var $row = $(this);
                $("#table-tipos tbody").find("input").prop("checked", false);
                $row.find("input").prop("checked", true);
            });

            checkAnotherOption();

            $('.option-type').on('change', function(event){
                checkAnotherOption();
            });

            selectAnotherProduct();

            $('#ref_product').on('change', function(event){
                selectAnotherProduct();
                selectHour()
            });

            $('.time-selector').datetimepicker({
                format: 'HH:mm'
            });

            $('#datetimepicker1').datetimepicker({
                format: 'DD/MM/YYYY'
            });

        })

        function checkAnotherOption(){
            var checked = $("#another_option").prop("checked");
            if(checked){
                $("#another_type").prop("disabled", false).closest(".form-group").show();
            }else{
                $("#another_type").val("").prop("disabled", true).closest(".form-group").hide();
            }
        }

        function selectAnotherProduct(){
            var product = $("#ref_product option:selected").text();
            if(product == "OTROS (DESCRIBIR)"){
                $("#ref_other_product").prop("disabled", false).closest(".row").show();
            }else {
                $("#ref_other_product").val("").prop("disabled", true).closest(".row").hide();
            }
        }

        function selectHour(){
            var product = $("#ref_product option:selected").text();
            switch (product) {
                case 'HAMBURGUESAS':
                    $("#ref_start").val("18:00");
                    $("#ref_end").val("22:00");
                    $("#ref_turn").val('NOCTURNO');
                    break;
                case 'RELLENOS':
                    $("#ref_start").val("09:00");
                    $("#ref_end").val("13:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'SALTEÑAS Y TUCUMANAS':
                    $("#ref_start").val("09:00");
                    $("#ref_end").val("13:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'VENTA DE DULCES':
                    $("#ref_start").val("08:00");
                    $("#ref_end").val("19:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'REFRIGERIOS':
                    $("#ref_start").val("08:00");
                    $("#ref_end").val("19:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'COPIA DE LLAVES':
                    $("#ref_start").val("08:00");
                    $("#ref_end").val("19:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'CASETA DE SEGURIDAD':
                    $("#ref_start").val("20:00");
                    $("#ref_end").val("06:00");
                    $("#ref_turn").val('TRASNOCHE');
                    break;
                case 'REFACCION DE ZAPATOS':
                    $("#ref_start").val("08:00");
                    $("#ref_end").val("19:00");
                    $("#ref_turn").val('DIURNO');
                    break;
                case 'OTROS (DESCRIBIR)':
                    $("#ref_start").val("");
                    $("#ref_end").val("");
                    $("#ref_turn").val('');
                    break;
            }
        }

        let initialPosition = {
            lat: {{old('lat', $model->lat)}}, lng: {{old('lng', $model->lng)}}
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

