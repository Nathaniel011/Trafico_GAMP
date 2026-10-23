<html>
<head>
<title>Datos obtenidos</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">


<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

<!-- JS, Popper.js, and jQuery -->
<script src="https://code.jquery.com/jquery-3.1.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<style>h5{color:#d52626;}
body { background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAaCAYAAACpSkzOAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABZ0RVh0Q3JlYXRpb24gVGltZQAxMC8yOS8xMiKqq3kAAAAcdEVYdFNvZnR3YXJlAEFkb2JlIEZpcmV3b3JrcyBDUzVxteM2AAABHklEQVRIib2Vyw6EIAxFW5idr///Qx9sfG3pLEyJ3tAwi5EmBqRo7vHawiEEERHS6x7MTMxMVv6+z3tPMUYSkfTM/R0fEaG2bbMv+Gc4nZzn+dN4HAcREa3r+hi3bcuu68jLskhVIlW073tWaYlQ9+F9IpqmSfq+fwskhdO/AwmUTJXrOuaRQNeRkOd5lq7rXmS5InmERKoER/QMvUAPlZDHcZRhGN4CSeGY+aHMqgcks5RrHv/eeh455x5KrMq2yHQdibDO6ncG/KZWL7M8xDyS1/MIO0NJqdULLS81X6/X6aR0nqBSJcPeZnlZrzN477NKURn2Nus8sjzmEII0TfMiyxUuxphVWjpJkbx0btUnshRihVv70Bv8ItXq6Asoi/ZiCbU6YgAAAABJRU5ErkJggg==);}
</style>
</head>
<body>








<section>
    <div class="container">
        <div class="row">

                <!--Profile Card 5-->
                <div class="col-md-12 mt-12">
                    <div class="card profile-card-5">
                        <div class="card-img-block">
<br>
<center><h4>GOBIERNO AUTONOMO MUNICIPAL DE POTOSI </h4></center>
    <center>
      <img class="rounded" src="{{asset('img/escudo.png')}}" alt="Card image cap" width="50%">
    </center>

                        </div>
                    <div class="card-body pt-0">
                    <h5><b>OCUPACION VIA</b></h5>

                    <p class="card-text"><b>Tipo:</b> {{$data->tipo}}</p>
                    <p class="card-text"><b>Solicitante:</b> {{$data->solicitante}}</p>
                    <p class="card-text"><b>Ci:</b> {{$data->ci}}</p>
                    <p class="card-text"><b>Rubro:</b> {{$data->rubro}}</p>

                    <h5><b>UBICACIÓN</b></h5>
                    <p class="card-text"><b>Nombre de Calle o Av.:</b> {{$data->direccion}}</p>

                    <h5><b>CARACTERÍSTICAS</b></h5>
                    <p class="card-text"><b>color:</b> {{$data->color}}</p>
                    <p class="card-text"><b>Material:</b> {{$data->material}}</p>
                    <p class="card-text"><b>Ancho:</b> {{$data->dim_ancho}}</p>
                    <p class="card-text"><b>Largo:</b> {{$data->dim_largo}}</p>

                    <h5><b>DIMENSION VÍA PEATONAL</b></h5>
                    <p class="card-text"><b>Tipo:</b> {{$data->tipo_via}}</p>
                    <p class="card-text"><b>Dimension:</b> {{$data->dim_via}}</p>
                    <p class="card-text"><b>% Ocupado:</b> {{$data->dim_perc_ocupado}}</p>

                    <h5><b>REFERENCIA EDIL</b></h5>
                    <p class="card-text"><b>Producto de Venta o Servicio:</b> {{$data->ref_product}}</p>
                    <p class="card-text"><b>Turno:</b> {{$data->ref_turn}}</p>
                    <p class="card-text"><b>Horario:</b> {{$data->ref_start}} a {{$data->ref_end}}</p>

                    <h5><b>COORDENADAS DE UBICACIÓN</b></h5>
                    <p class="card-text"><b>LATITUD:</b> {{$data->lat}} </p>
                    <p class="card-text"><b>LONGITUD:</b> {{$data->lng}} </p>

                    <h5><b>DETERMINACIÓN INFORME TÉCNICO</b></h5>
                    <p class="card-text"><b>PROCEDENTE:</b> {{($data->technical_proceed)?'SI':'NO'}} </p>
                    <h5><b>DETERMINACIÓN INFORME TÉCNICO</b></h5>
                    <p class="card-text"><b>PROCEDENTE:</b> {{($data->legal_proceed)?'SI':'NO'}}</p>

                  </div>
                </div>
                </div>
        </div>
    </div>
</section>


</html>
