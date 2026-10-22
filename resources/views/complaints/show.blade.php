@extends('layouts.admin')

@section('header')
    <h1>Denuncia <small>(Mostrar)</small></h1>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
        </div>

            <div class="card-body">
                <div class="row justify-content-center">
                    <div class="col-8">

                        <div class="form-group row">
                            <div class="col-sm-12 text-center">
                                @if(strlen($model->image))
                                    <img width="196" class="img img-fluid" src="{{asset($model->image)}}" alt="">
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Fecha</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('name', $model->formatted_created_at)}}" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Placa</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('name', $model->vehicle_plaque)}}" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="name" class="col-sm-2 col-form-label">Linea</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control form-control-sm" value="{{old('name', $model->route_code)}}" disabled>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="body" class="col-sm-2 col-form-label">Notas</label>
                            <div class="col-sm-10">
                                <div style="background: #e9ecef; padding: .25rem .5rem; border-radius: .2rem; border: 1px solid #ced4da">
                                    {!! nl2br(e($model->notes)) !!}
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Estado</label>

                            <div class="col-sm-10">
                                <a href="#" id="btn-accepted" class="btn-status btn btn-lg {{$model->state == 'ACEPTADO'? 'btn-primary': 'btn-outline-primary'}}" role="button" aria-pressed="true">Aceptado</a>
                                <a href="#" id="btn-declined" class="btn-status btn btn-lg {{$model->state == 'RECHAZADO'? 'btn-danger': 'btn-outline-danger'}}" role="button" aria-pressed="true">Rechazado</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
                <a href="{{route('complaints.index')}}" class="btn btn-default">Volver</a>
            </div>

        <!-- /.card-footer-->
    </div>
    <!-- /.card -->


@endsection

@push('styles')

@endpush

@push('scripts')
    <script>
        $(function(){
            $("#btn-accepted").on("click", function(e){
                e.preventDefault();
                var $btn = $(this);
                if($btn.hasClass("btn-outline-primary")){
                    $.ajax({
                        url: "{{route('complaints.state', $model->id)}}",
                        data: {
                            state: "ACEPTADO",
                            _token: "{{csrf_token()}}"
                        },
                        method: "POST",
                        success: function(data){
                            if(data.success){
                                $btn.removeClass("btn-outline-primary");
                                $btn.addClass("btn-primary");

                                $("#btn-declined").removeClass("btn-danger");
                                $("#btn-declined").addClass("btn-outline-danger");
                            }
                        }
                    });
                }
            });

            $("#btn-declined").on("click", function(e){
                e.preventDefault();
                var $btn = $(this);
                if($btn.hasClass("btn-outline-danger")){
                    $.ajax({
                        url: "{{route('complaints.state', $model->id)}}",
                        data: {
                            state: "RECHAZADO",
                            _token: "{{csrf_token()}}"
                        },
                        method: "POST",
                        success: function(data){
                            if(data.success){
                                $btn.removeClass("btn-outline-danger");
                                $btn.addClass("btn-danger");

                                $("#btn-accepted").removeClass("btn-primary");
                                $("#btn-accepted").addClass("btn-outline-primary");
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush

