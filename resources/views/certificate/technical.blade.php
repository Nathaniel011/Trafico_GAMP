@extends('layouts.admin')

@section('header')
    <h1>Certificación de inspección <small>(Etapa Técnica)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header bg-white  text-white rounded-top">
            <h5 class="mb-3">Resumen de la solicitud (Etapa legal)</h5>
            <div class="row justify-content-center g-3 p-3">
                <div class="col-12 col-md-8 rounded shadow border">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>Solicitante:</strong><br>{{ $model->solicitante }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>C.I.:</strong><br>{{ $model->ci }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>Rubro:</strong><br>{{ $model->rubro }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>Tipo:</strong><br>{{ $model->tipo }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>Modalidad:</strong><br>{{ $model->modalidad }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light my-3 mx-3">
                                <strong>Observaciones legales:</strong><br>
                                {{ $model->inspection_date ? 'Etapa legal aprobada.' : $model->conclusions }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h3>Datos técnicos del certificado</h3>
        <form action="{{ route('certificate.technical.store', $model->id) }}" method="POST" class="form-horizontal">
            {{csrf_field()}}
            <input type="hidden" name="certificate_version" value="{{ old('certificate_version', \App\Services\CertificateHistory::version($model)) }}">
            @error('certificate_version')<p class="text-danger m-3">{{ $message }}</p>@enderror
            @if(config('features.caseta_dossier'))
            <label class="m-3 d-block">Motivo de la corrección<textarea class="form-control" name="revision_reason" required maxlength="2000">{{ old('revision_reason') }}</textarea></label>
            @error('revision_reason')<p class="text-danger m-3">{{ $message }}</p>@enderror
            @endif
            @error('location_confirmed')<p class="text-danger m-3">{{ $message }}</p>@enderror
            @error('location_source')<p class="text-danger m-3">{{ $message }}</p>@enderror
            @error('location_reason')<p class="text-danger m-3">{{ $message }}</p>@enderror
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Fecha inspeccción</label>
                            <div class="col-sm-4">
                                <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
                                    <input type="text" class="form-control datetimepicker-input" value="{{ old('inspection_date', $model->inspection_date ? \Carbon\Carbon::parse($model->inspection_date)->format('d/m/Y') : '') }}" data-target="#datetimepicker1" name="inspection_date"/>
                                    <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                                @error('inspection_date')
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



@include('casetas.location', ['location' => $location])

                        <h5>Determinación informe técnico</h5>
                        <div class="form-group row justify-content-center">
                            <div class="col-sm-4">
                                <div class="icheck-primary d-inline">
                                    <input class="option-type" type="radio" id="proceed_technical_yes" name="technical_proceed" value="1" {{old('technical_proceed', $model->technical_proceed)=='1'? 'checked': ''}}>
                                    <label for="proceed_technical_yes">PROCEDENTE</label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="icheck-primary d-inline ml-4">
                                    <input class="option-type" type="radio" id="proceed_technical_no" name="technical_proceed" value="0" {{old('technical_proceed', $model->technical_proceed)=='0'? 'checked': ''}}>
                                    <label for="proceed_technical_no">IMPROCEDENTE</label>
                                </div>
                            </div>
                            @error('technical_proceed')
                                <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <h5>Observaciones adicionales</h5>
                        <div class="form-group row justify-content-center">
                            <div class="col-sm-12">
                                <textarea name="conclusions" class="form-control" rows="5">{{old('conclusions', $model->inspection_date ? (str_starts_with((string) $model->conclusions, 'REALIZADA LA INSPECCIÓN') ? (explode("\n\n", $model->conclusions, 2)[1] ?? '') : $model->conclusions) : '')}}</textarea>
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
            <!-- /.card-footer-->
        </form>
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
    <script>
        $(document).ready(function(){

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

    </script>
@endpush
