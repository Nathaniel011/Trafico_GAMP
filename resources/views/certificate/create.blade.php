@extends('layouts.admin')

@section('header')
    <h1>Certificación de inspección <small>(Etapa Legal)</small></h1>
@endsection

@section('content')
    <div class="card">
        <form action="{{route('certificate.store')}}" method="POST" class="form-horizontal">
            {{csrf_field()}}
            <input type="hidden" name="caseta_id" value="{{ old('caseta_id', $model->caseta_id) }}">
            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8">
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
                                @error('type')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
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

                                <div class="icheck-primary d-inline ml-4">
                                    <input class="option-type" type="radio" id="radioPrimary7" name="mode" value="RENOVACION CON CAMBIO DE RUBRO" {{old('mode', $model->modalidad)=='RENOVACION CON CAMBIO DE RUBRO'? 'checked': ''}}>
                                    <label for="radioPrimary7">RENOVACION CON CAMBIO DE RUBRO</label>
                                </div>

                                <div class="icheck-primary d-inline ml-4">
                                    <input class="option-type" type="radio" id="radioPrimary8" name="mode" value="RENOVACION CON CAMBIO DE NOMBRE" {{old('mode', $model->modalidad)=='RENOVACION CON CAMBIO DE NOMBRE'? 'checked': ''}}>
                                    <label for="radioPrimary8">RENOVACION CON CAMBIO DE NOMBRE</label>
                                </div>

                                @error('mode')
                                <div class="badge badge-danger">{{ $message }}</div>
                                @enderror
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

                        <h5>Determinación informe legal</h5>
                        <div class="form-group row justify-content-center">
                            <div class="col-sm-4">
                                <div class="icheck-primary d-inline">
                                    <input class="option-type" type="radio" id="proceed_legal_yes" name="legal_proceed" value="1" {{old('legal_proceed', $model->legal_proceed)=='1'? 'checked': ''}}>
                                    <label for="proceed_legal_yes">PROCEDENTE</label>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="icheck-primary d-inline ml-4">
                                    <input class="option-type" type="radio" id="proceed_legal_no" name="legal_proceed" value="0" {{old('legal_proceed', $model->legal_proceed)=='0'? 'checked' :'' }}>
                                    <label for="proceed_legal_no">IMPROCEDENTE</label>
                                </div>
                            </div>
                            @error('legal_proceed')
                                <div class="badge badge-danger">{{ $message }}</div>
                            @enderror
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
            <!-- /.card-footer -->
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
    <script src="{{asset('plugins/icheck-bootstrap/icheck.js')}}"></script>
    <script src="{{asset('plugins/moment/moment.min.js')}}"></script>
    <script src="{{asset('plugins/bootstrap-datetimepicker/js/tempusdominus-bootstrap-4.min.js')}}"></script>
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
        })

        function checkAnotherOption(){
            var checked = $("#another_option").prop("checked");
            if(checked){
                $("#another_type").prop("disabled", false).closest(".form-group").show();
            }else{
                $("#another_type").val("").prop("disabled", true).closest(".form-group").hide();
            }
        }
    </script>
@endpush

