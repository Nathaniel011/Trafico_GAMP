@extends('layouts.public')

@section('content')

        <!-- Inicio Section-->
        <section class="page-section text-center" id="parking">

            <p class="page-section-heading text-uppercase text-secondary" id='map_title'>PUNTOS DE PARQUEO<br/>VEHÍCULOS</p>
            <button type="button" class="btn btn-primary" id='bcar'>VEHÍCULOS</button>
            <button type="button" class="btn btn-primary" id='bmoto'>MOTOCICLETAS</button>
            <button type="button" class="btn btn-primary" id='bperimeter'>PERIMETRO</button>
            <div id="map" style="height: 700px;"></div>

        </section>
        <!-- Acerca de Section-->
        <section class="page-section bg-primary text-white mb-0" id="about">
            <div class="container">
                <!-- About Section Heading-->
                <h2 class="page-section-heading text-center text-uppercase text-white">Acerca de Tráfico y Vialidad</h2>
                <!-- Icon Divider-->
                <div class="divider-custom divider-light">
                    <div class="divider-custom-line"></div>
                    <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
                    <div class="divider-custom-line"></div>
                </div>
                <!-- About Section Content-->
                <div class="row">
                    <div class="col-lg-4 ms-auto"><h3 class="masthead-heading">Misión</h3><p class="lead">Garantizar la fluidez y seguridad del tránsito terrestre de personas y vehículos y del transporte público y privado mediante la planeación, organización y control del tránsito municipal, la aplicación de las normas que regulan esta materia, la imposición de sanciones y multas correspondientes a las infracciones y la organización ágil y eficiente del registro de vehículos y conductores de conformidad con las competencias asignadas por la ley y los reglamentos..</p></div>
                    <div class="col-lg-4 me-auto"><h3 class="masthead-heading">Visión</h3><p class="lead">La visión de la Secretaria de Tránsito y Transporte del municipio de Popayán es ser un grupo eficiente en lo relacionado con la seguridad y control del tránsito y líder en servicios y trámites concernientes a los vehículos particulares y públicos del municipio. Sistematización total de los registros únicos nacionales de tránsito y transporte que involucran registros nacionales de conductores y automotores. La Secretaria de Tránsito y Transporte del municipio de Popayán busca la optimización y armonía en su personal, para alcanzar una contribución positiva al ambiente laboral y de esta forma ofrecer un excelente servicio y atención al público.</p></div>
                </div>
                <!-- About Section Button-->
                <div class="text-center mt-4">
                    <a class="btn btn-xl btn-outline-light" >
                        <i class="fas fa-download me-2"></i>
                        Descarga Reglamento!
                    </a>
                </div>
            </div>
        </section>
        <!-- Contacto Section-->
        <section class="page-section" id="contact">
            <div class="container">
                <!-- Contact Section Heading-->
                <h2 class="page-section-heading text-center text-uppercase text-secondary mb-0">Contáctate</h2>
                <!-- Icon Divider-->
                <div class="divider-custom">
                    <div class="divider-custom-line"></div>
                    <div class="divider-custom-icon"><i class="fas fa-star"></i></div>
                    <div class="divider-custom-line"></div>
                </div>
                <!-- Contact Section Form-->
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <!-- * * * * * * * * * * * * * * *-->
                        <!-- * * SB Forms Contact Form * *-->
                        <!-- * * * * * * * * * * * * * * *-->
                        <!-- This form is pre-integrated with SB Forms.-->
                        <!-- To make this form functional, sign up at-->
                        <!-- https://startbootstrap.com/solution/contact-forms-->
                        <!-- to get an API token!-->
                        <form id="contactForm" data-sb-form-api-token="API_TOKEN">
                            <!-- Name input-->
                            <div class="form-floating mb-3">
                                <input class="form-control" id="name" type="text" placeholder="Enter your name..." data-sb-validations="required" />
                                <label for="name">Full name</label>
                                <div class="invalid-feedback" data-sb-feedback="name:required">A name is required.</div>
                            </div>
                            <!-- Email address input-->
                            <div class="form-floating mb-3">
                                <input class="form-control" id="email" type="email" placeholder="name@example.com" data-sb-validations="required,email" />
                                <label for="email">Email address</label>
                                <div class="invalid-feedback" data-sb-feedback="email:required">An email is required.</div>
                                <div class="invalid-feedback" data-sb-feedback="email:email">Email is not valid.</div>
                            </div>
                            <!-- Phone number input-->
                            <div class="form-floating mb-3">
                                <input class="form-control" id="phone" type="tel" placeholder="(123) 456-7890" data-sb-validations="required" />
                                <label for="phone">Phone number</label>
                                <div class="invalid-feedback" data-sb-feedback="phone:required">A phone number is required.</div>
                            </div>
                            <!-- Message input-->
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="message" type="text" placeholder="Enter your message here..." style="height: 10rem" data-sb-validations="required"></textarea>
                                <label for="message">Message</label>
                                <div class="invalid-feedback" data-sb-feedback="message:required">A message is required.</div>
                            </div>
                            <!-- Submit success message-->
                            <!---->
                            <!-- This is what your users will see when the form-->
                            <!-- has successfully submitted-->
                            <div class="d-none" id="submitSuccessMessage">
                                <div class="text-center mb-3">
                                    <div class="fw-bolder">Form submission successful!</div>
                                    To activate this form, sign up at
                                    <br />
                                    <a href="https://startbootstrap.com/solution/contact-forms">https://startbootstrap.com/solution/contact-forms</a>
                                </div>
                            </div>
                            <!-- Submit error message-->
                            <!---->
                            <!-- This is what your users will see when there is-->
                            <!-- an error submitting the form-->
                            <div class="d-none" id="submitErrorMessage"><div class="text-center text-danger mb-3">Error sending message!</div></div>
                            <!-- Submit Button-->
                            <button class="btn btn-primary btn-xl disabled" id="submitButton" type="submit">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    <!-- *************************************** -->

@endsection

@push('scripts')
    <script src="{{asset('plugins/jQuery-3.6.0/jquery-3.6.0.min.js')}}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap1"
        async
    ></script>
    <script>
        $('#bcar').on('click',function(){
            $.getScript("https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap1");
            $("#map_title").html("PUNTOS DE PARQUEO<br/>VEHÍCULOS");
        });
        $('#bmoto').on('click',function(){
            $.getScript("https://maps.googleapis.com/maps/api/js?key=AIzaSyDWfhkbkuAlTm08GKlCAUcINKqXUSTdzqE&callback=initMap2");
            $("#map_title").html("PUNTOS DE PARQUEO<br/>MOTOCICLETAS");
        });
        $('#bperimeter').on('click',function(){
            $("#map").html('<iframe src="https://www.google.com/maps/d/u/0/embed?mid=1kcmXmj_z5gJVflUyFWdQTnllO3BvkJ4&ehbc=2E312F" width="100%" height="680"></iframe>');
            $("#map_title").html("RESTRICCIÓN VEHICULAR<br/>PERIMETRO");
        });
        function initMap1() {
            let initialLocation = {
                lat: -19.58918, lng: -65.75348
            };

            map = new google.maps.Map(document.getElementById("map"), {
                center: initialLocation,
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
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    initialLocation = {lat: position.coords.latitude, lng:position.coords.longitude};
                    map.setCenter(initialLocation);
                    let marker = new google.maps.Marker({
                        position: initialLocation,
                        map,
                        title: 'Ubicación Actual'
                    });
                });
            }

            url  = '{{ asset("/public/carpoints")}}/';
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

        function initMap2() {
            let initialPosition = {
                lat: -19.58918, lng: -65.75348
            };

            map = new google.maps.Map(document.getElementById("map"), {
                center: initialPosition,
                zoom: 15,
            });
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    initialLocation = {lat: position.coords.latitude, lng:position.coords.longitude};
                    map.setCenter(initialLocation);
                    let marker = new google.maps.Marker({
                        position: initialLocation,
                        map,
                        title: 'Ubicación Actual'
                    });
                });
            }
            url  = '{{ asset("/public/motopoints")}}/';
            $.getJSON(url, null, function(data){
                $.each(data, function(index, p){
                    let html = "<center><h5>"+p.name+"</h5></center><center><h6>"+p.description+"</h6>Zona: "+p.zone+"<br />Espacios: "+p.quantity+"<br /></center>";
                    let infoWindow = new google.maps.InfoWindow({content: html, maxWidth: 250});
                    let xy = new google.maps.LatLng(p.lat, p.lng);
                    let image = '{{ asset("/img/moto.png")}}';
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
