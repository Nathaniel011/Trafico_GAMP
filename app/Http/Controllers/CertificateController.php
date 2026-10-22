<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Yajra\DataTables\DataTables;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:edit-certificates')->only(['edit', 'update']);
    }

    public function index(){
        $certificates = Certificate::all();

        return view('certificate.index', [
            'certificates' => $certificates
        ]);
    }

    public function shops(){
        $certificates = Certificate::all();

        return view('certificate.shops', [
            'shops' => $certificates
        ]);
    }

    public function data(){
        return DataTables::of(Certificate::all())
            ->addColumn('actions', function ($cert) {
                $editRoute = route('certificate.edit', $cert->id);
                $btnEdit = '';
                if(str_contains((string) auth()->user()->roles, 'ADMIN')){
                    $btnEdit = "<a href='$editRoute' class='btn btn-xs btn-primary'><i class='fa fa-edit'></i> Editar</a>";
                }
                $showRoute = route('certificate.show', $cert->id);
                $showqrRoute = route('certificate.showqr', $cert->id);
                return "$btnEdit <a href='$showRoute' class='btn btn-xs btn-primary'><i class='fa fa-eye'></i> Mostrar</a> <a href='$showqrRoute' class='btn btn-xs btn-primary'><i class='fa fa-eye'></i> QR</a>";
            })
            ->editColumn('created_at', function($cert){
                $date = new Carbon($cert->created_at);
                return $date->format('d/m/Y');
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create(){
        $model = new Certificate();

//        $default_reference = Reference::where('types', 'OTRO')->first();
//        if($default_reference){
//            $model->reference_id = $default_reference->id;
//        }
        $model->lat = -19.5892639;
        $model->lng = -65.7536236;

        return view('certificate.create', [
            'model' => $model
        ]);
    }

    public function store(Request $request){

        $request->validate([
            'type' => 'required',
            'mode' => 'required',
            'another_type' => 'required_if:type,OTROS',
            'solicitante' => 'required',
            'ci' => 'required|unique:certificates,ci',//TODO: unique solo si es nuevo o regularizacion
            'celular' => 'nullable',
            'rubro' => 'required',
            'direccion' => 'required',
            'dim_ancho' => 'required|numeric',
            'dim_largo' => 'required|numeric',
            'tipo_via' => 'required',
            'dim_via' => 'required|numeric',
            'dim_perc_ocupado' => 'nullable|numeric',
            'ref_product' => 'required',
            'ref_turn' => 'required',
            'ref_start' => 'required|date_format:H:i',
            'ref_end' => 'required|date_format:H:i',
            'color' => 'required',
            'material' => 'required',
            //'conclusions' => 'required',
            'inspection_date' => 'required|date_format:d/m/Y'
        ], [
            'required' => 'Requerido',
            'another_type.required_if' => 'Debes llenar este campo',
            'numeric' => 'Solo se permiten numeros',
            'date_format' => 'Formato incorrecto',
            'unique' => 'Este CI ya esta registrado'
        ]);

        $model = new Certificate();
        $model->tipo = strtoupper($request->type);
        if($model->tipo=='OTROS'){
            $model->tipo = strtoupper($request->another_type);
        }
        $model->modalidad = $request->mode;
        $model->solicitante = strtoupper($request->solicitante);
        $model->ci = strtoupper($request->ci);
        $model->celular = strtoupper((string) $request->celular);
        $model->rubro = strtoupper($request->rubro);
        $model->direccion = strtoupper($request->direccion);
        $model->dim_ancho = $request->dim_ancho;
        $model->dim_largo = $request->dim_largo;

        $model->tipo_via = $request->tipo_via;
        $model->dim_via = $request->dim_via;
        $model->dim_perc_ocupado = $request->dim_perc_ocupado;

        $model->ref_product = strtoupper($request->ref_product);
        if($model->ref_product=='OTROS (DESCRIBIR)')
            $model->ref_product = strtoupper((string) $request->ref_other_product);
        $model->ref_turn = strtoupper($request->ref_turn);

        $model->ref_start = $request->ref_start;
        $model->ref_end = $request->ref_end;

        $name = $model->solicitante;
        $document = $model->ci;
        $type = '';
        if($model->modalidad == 'NUEVO'){
            $type = 'OTORGACIÓN';
        }else{
            $type = $model->modalidad;
        }
        $text = "REALIZADA LA INSPECCIÓN DE LA CASETA DEL(DE LA) SR(A). $name, CI: $document, EN CUANTO A SUS DIMENSIONES SE ENCUENTRA DENTRO DE LO DETERMINADO SEGÚN DECRETO EDIL N° 014/2015 Y SU MODIFICATORIO, EN CONSECUENCIA, BAJO ESTOS ANTECEDENTES SEÑALADOS, ES VIABLE LA PROSECUCIÓN DEL TRAMITE PARA LA $type DE LICENCIA DE FUNCIONAMIENTO, ADEMAS QUE LA UBICACIÓN DE LA CASETA SE ENCUENTRA EN UN LUGAR ACCESIBLE QUE NO PERJUDICA EL TRABAJO PEATONAL NI VEHICULAR.";
        $text = $text."\n\n".$request->conclusions;
        if($request->technical_proceed===0){
            $text = $request->conclusions;
        }
        $model->conclusions = $text;

        $model->technical_proceed = $request->technical_proceed;
        $model->legal_proceed = $request->legal_proceed;
        $model->color= strtoupper($request->color);
        $model->material = strtoupper($request->material);
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $i_date = Carbon::createFromFormat('d/m/Y', $request->inspection_date);
        $model->inspection_date = $i_date->format('Y-m-d');
        $model->save();
        return redirect()->route('certificate.show', $model->id);
    }

    public function edit($id){
        $model = Certificate::findOrFail($id);

        return view('certificate.edit', [
            'model' => $model
        ]);
    }

    public function update(Request $request, $id){

        $request->validate([
            'type' => 'required',
            'mode' => 'required',
            'another_type' => 'required_if:type,OTROS',
            'solicitante' => 'required',
            'ci' => 'required|unique:certificates,ci,'.$id,
            'celular' => 'nullable',
            'rubro' => 'required',
            'direccion' => 'required',
            'dim_ancho' => 'required|numeric',
            'dim_largo' => 'required|numeric',
            'tipo_via' => 'required',
            'dim_via' => 'required|numeric',
            'dim_perc_ocupado' => 'nullable|numeric',
            'ref_product' => 'required',
            'ref_turn' => 'required',
            'ref_start' => 'required|date_format:H:i',
            'ref_end' => 'required|date_format:H:i',
            'color' => 'required',
            'material' => 'required',
            //'conclusions' => 'required',
            'lng' => 'required|numeric',
            'lng' => 'required|numeric',
            'inspection_date' => 'required|date_format:d/m/Y'
        ], [
            'required' => 'Requerido',
            'another_type.required_if' => 'Debes llenar este campo',
            'numeric' => 'Solo se permiten numeros',
            'date_format' => 'Formato incorrecto',
            'unique' => 'Este CI ya esta registrado'
        ]);

        $model = Certificate::findOrFail($id);
        $model->modalidad = $request->mode;
        $model->tipo = strtoupper($request->type);
        if($model->tipo=='OTROS'){
            $model->tipo = strtoupper($request->another_type);
        }
        $model->solicitante = strtoupper($request->solicitante);
        $model->ci = strtoupper($request->ci);
        $model->celular = strtoupper((string) $request->celular);
        $model->rubro = strtoupper($request->rubro);
        $model->direccion = strtoupper($request->direccion);
        $model->dim_ancho = $request->dim_ancho;
        $model->dim_largo = $request->dim_largo;

        $model->tipo_via = $request->tipo_via;
        $model->dim_via = $request->dim_via;
        $model->dim_perc_ocupado = $request->dim_perc_ocupado;

        $model->ref_product = strtoupper($request->ref_product);
        if($model->ref_product=='OTROS (DESCRIBIR)')
            $model->ref_product = strtoupper((string) $request->ref_other_product);
        $model->ref_turn = strtoupper($request->ref_turn);
        $model->ref_start = $request->ref_start;
        $model->ref_end = $request->ref_end;

        $name = $model->solicitante;
        $document = $model->ci;
        $type = '';
        if($model->modalidad == 'NUEVO'){
            $type = 'OTORGACIÓN';
        }else{
            $type = $model->modalidad;
        }
        $text = "REALIZADA LA INSPECCIÓN DE LA CASETA DEL(DE LA) SR(A). $name, CI: $document, EN CUANTO A SUS DIMENSIONES SE ENCUENTRA DENTRO DE LO DETERMINADO SEGÚN DECRETO EDIL N° 014/2015 Y SU MODIFICATORIO, EN CONSECUENCIA, BAJO ESTOS ANTECEDENTES SEÑALADOS, ES VIABLE LA PROSECUCIÓN DEL TRAMITE PARA LA $type DE LICENCIA DE FUNCIONAMIENTO, ADEMAS QUE LA UBICACIÓN DE LA CASETA SE ENCUENTRA EN UN LUGAR ACCESIBLE QUE NO PERJUDICA EL TRABAJO PEATONAL NI VEHICULAR.";
        $text = $text."\n\n".$request->conclusions;
        if($request->technical_proceed===0){
            $text = $request->conclusions;
        }
        $model->conclusions = $text;

        $model->technical_proceed = $request->technical_proceed;
        $model->legal_proceed = $request->legal_proceed;
        $model->color= strtoupper($request->color);
        $model->material = strtoupper($request->material);
        $model->lng = $request->lng;
        $model->lat = $request->lat;
        $i_date = Carbon::createFromFormat('d/m/Y', $request->inspection_date);
        $model->inspection_date = $i_date->format('Y-m-d');
        $model->save();
        return redirect()->route('certificate.show', $model->id);
    }

    public function show($id){
        $model = Certificate::findOrFail($id);
        return view('certificate.show', [
            'model' => $model
        ]);
    }

    public function showqr($id){
        $model = Certificate::findOrFail($id);
        return view('certificate.showqr', [
            'model' => $model
        ]);
    }

    public function showmap($id){
        $model = Certificate::findOrFail($id);
        return view('certificate.showmap', [
            'model' => $model
        ]);
    }

    public function pdf($id){
        $model = Certificate::findOrFail($id);


        PDF::setHeaderCallback(function($pdf){
            $pdf->SetFont('times', 'B', 12);
            $pdf->SetY(10);
            $pdf->MultiCell(0,15, "GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ\nJEFATURA DE TRÁFICO Y VIALIDAD", 1, 'C',0,0);

            $pdf->Image(public_path('img/escudo.png'), 25, 11, '', 13, '', '', 'T', false, 500, '', false, false, 0, false, false, false);
            $pdf->Image(public_path('img/potosi.png'), 170, 11, '', 13, '', '', 'T', false, 500, '', false, false, 0, false, false, false);

//            $pdf->MultiCell(0,15, "GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ\nJEFATURA DE TRÁFICO Y VIALIDAD", 1, 'C',0,0);
        });

        PDF::setFooterCallback(function($pdf) use ($model){

            $date = '...';
            if($model->inspection_date){
                $date = (new Carbon($model->inspection_date))->format('d/m/Y');
            }

            $pdf->SetFont('times', '', 8);
            $pdf->SetY(258);
            $pdf->Cell(92, 4, 'Fecha de Inspección: '.$date, 0, 0);
            $pdf->Cell(92, 4, 'Fecha de Impresión: '.date('d/m/Y'), 0, 1, 'R');

//            $pdf->MultiCell(0,15, "GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ\nJEFATURA DE TRÁFICO Y VIALIDAD", 1, 'C',0,0);
        });


        PDF::SetPageUnit('mm');
        PDF::SetMargins(15, 28, 15, 15);
        PDF::SetTitle('Certificado');
        PDF::AddPage('P', 'LETTER');
        PDF::SetFont('helvetica', 'B', 11);

        PDF::Cell(0,0,'CERTIFICADO DE INSPECCIÓN', 0, 1, 'C');
        PDF::Ln();

        PDF::SetFont('times', 'B', 12);
        PDF::Cell(60,0,'OCUPACIÓN DE VIA', 0, 1);
        PDF::SetFillColor(86,101,115);

        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(60, 0, '');PDF::Cell(25, 5, 'CASETA', 0);PDF::Cell(10, 5, '', 1, 1, '', $model->tipo=='CASETA'?1:0);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(60, 0, '');PDF::Cell(25, 5, 'KIOSKO', 0);PDF::Cell(10, 5, '', 1, 1, '', $model->tipo=='KIOSKO'?1:0);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(60, 0, '');PDF::Cell(25, 5, 'ANAQUEL', 0);PDF::Cell(10, 5, '', 1, 1, '', $model->tipo=='ANAQUEL'?1:0);
        PDF::SetY(PDF::GetY()+1);
        $isAnother = !in_array($model->tipo, ['CASETA', 'ANAQUEL', 'KIOSKO']);
        PDF::Cell(60, 0, '');PDF::Cell(25, 5, 'OTROS', 0);PDF::Cell(10, 5, '', 1, $isAnother? 0: 1, '', $isAnother?1:0);
        PDF::SetFillColor(213,216,220);
        if($isAnother){
            PDF::Cell(1, 0, '', 0, 0);
            PDF::Cell(70, 5, $model->tipo, 0, 1, 'C', 1);
        }

        PDF::Ln();
        PDF::SetFont('times', '', 12);
        PDF::Cell(60,0,'SOLICITANTE', 0, 1);

        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(30, 0);
        PDF::Cell(25, 0, 'NOMBRE'); PDF::Cell(95, 0, $model->solicitante, 0, 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(35, 0);
        PDF::Cell(20, 0, 'C.I.'); PDF::Cell(30, 0, $model->ci, 0, 0, '', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'CELULAR'); PDF::Cell(30, 0, $model->celular, 0, 1, '', 1);

        PDF::SetY(PDF::GetY()+10);
        PDF::SetFont('times', '', 12);
        PDF::Cell(55,0,'RUBRO', 0, 0);
        PDF::SetFont('helvetica', '', 11);
        PDF::Cell(0, 0, $model->rubro, 0, 1, 'C', 1, null, 1);

        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        PDF::Cell(60, 0,'UBICACIÓN', 0, 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('times', '', 12);
        PDF::Cell(55, 0,'NOMBRE DE CALLE O AV.', 0, 0, '', 0, null, 1);
        PDF::SetFont('helvetica', '', 11);
        PDF::Cell(0, 0, $model->direccion, 0, 1, 'C', 1, null, 1);

        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        PDF::Cell(60, 0,'CARACTERÍSTICAS', 0, 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(30, 0);
        PDF::Cell(25, 0, 'COLOR'); PDF::Cell(30, 0, $model->color, 0, 0, 'C', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'MATERIAL'); PDF::Cell(30, 0, $model->material, 0, 1, 'C', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(30, 0);
        PDF::Cell(25, 0, 'ANCHO'); PDF::Cell(30, 0, $model->dim_ancho, 0, 0, 'C', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'LARGO'); PDF::Cell(30, 0, $model->dim_largo, 0, 1, 'C', 1);
        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        PDF::Cell(80, 0,'DIMENSIÓN DE VÍA PEATONAL', 0, 1, '', 0, null, 1);
        PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, 'TIPO', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->tipo_via, 0, 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, 'DIMENSION', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->dim_via, 0, 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, '% OCUPADO', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->dim_perc_ocupado, 0, 1, '', 1);
        PDF::Ln();

        PDF::SetFont('times', 'B', 12);
        PDF::Cell(60, 0,'REFERENCIA EDIL', 0, 1);

        PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'PRODUCTO DE VENTA O SERVICIO', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(100, 5, $model->ref_product, 0, 1, 'C', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'TURNO', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(100, 5, $model->ref_turn, 0, 1, 'C', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'HORARIO', 0, 0, '', null, 0, 1);
        PDF::Cell(2, 0);
        PDF::Cell(10, 5, 'De', 0, 0, 'C', 0);
        $start = Carbon::createFromFormat('H:i:s', $model->ref_start);
        PDF::Cell(40, 5, $start->format('H:i'), 0, 0, 'C', 1);

        PDF::Cell(10, 5, 'A', 0, 0, 'C', 0);
        $end = Carbon::createFromFormat('H:i:s', $model->ref_end);
        PDF::Cell(40, 5, $end->format('H:i'), 0, 1, 'C', 1);
        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        //PDF::Cell(60, 0,'CROQUIS DE UBICACION', 0, 1);
        //style=feature:poi|visibility:off
        //$url = "https://maps.googleapis.com/maps/api/staticmap?zoom=17&size=600x300&center={$model->lat},{$model->lng}&markers=color:green|{$model->lat},{$model->lng}&key=AIzaSyCYChzOSw1AE2xAbWVCOmwAzy4UeG_XTUw";

        //$margin =
        //    PDF::Image($url, '', '', 100, 50);

        //dd($url);

        PDF::Cell(100, 0,'COORDENADAS DE UBICACIÓN', 0, 0);
        PDF::Cell(60, 0,'CODIGO QR', 0, 1);PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+5);
        PDF::Cell(15, 0, '');PDF::Cell(25, 5, 'LATITUD', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(35, 5, $model->lat, 0, 1, 'C', 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(15, 0, '');PDF::Cell(25, 5, 'LONGITUD', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(35, 5, $model->lng, 0, 1, 'C', 1);
        PDF::SetY(PDF::GetY()+1);

        PDF::Write2DBarcode(route('certificates.verify', $model->code), 'QRCODE,M', PDF::GetX() + 100, PDF::GetY()-15, 40, 40);

        PDF::AddPage();
        //PDF::SetY(PDF::GetY()+40);
        PDF::SetFont('helvetica', 'B', 8.5);
        PDF::MultiCell(0, 0, 'CROQUIS DE UBICACIÓN. SOLICITANTE: '.$model->solicitante, 0, 'C', 0, 1, '', '', true);
        $lat = $model->lat;
        $lng = $model->lng;
        $mapboxToken = rawurlencode((string) config('services.mapbox.token', ''));
        $url = "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-l+000($lng,$lat)/$lng,$lat,16/280x220?access_token={$mapboxToken}";
        PDF::Image($url, 55, '', 0, 0, '', null, '', false, 300);
        PDF::Cell(0, 80, '', 0, 1);
        PDF::SetFont('times', 'B', 12);
        PDF::Cell(80, 0,'DETERMINACIÓN INFORME LEGAL', 0, 1, '', 0, null, 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(20, 0);
        PDF::Cell(30, 0, 'PROCEDENTE', 0, 0, 'R'); PDF::Cell(2, 0); PDF::Cell(10, 0, $model->legal_proceed?'SI':'',1, 0, 'C', $model->legal_proceed?1: 0);
        PDF::Cell(20, 0, '');
        PDF::Cell(30, 0, 'IMPROCEDENTE', 0, 0, 'R'); PDF::Cell(2, 0); PDF::Cell(10, 0, !$model->legal_proceed?'SI':'', 1, 1, 'C', !$model->legal_proceed?1: 0);

        PDF::SetFont('times', 'B', 12);
        PDF::Ln();
        PDF::Cell(80, 0,'DETERMINACIÓN INFORME TÉCNICO', 0, 1, '', 0, null, 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(20, 0);
        PDF::Cell(30, 0, 'PROCEDENTE', 0, 0, 'R'); PDF::Cell(2, 0); PDF::Cell(10, 0, $model->technical_proceed?'SI':'',1, 0, 'C', $model->technical_proceed?1: 0);
        PDF::Cell(20, 0, '');
        PDF::Cell(30, 0, 'IMPROCEDENTE', 0, 0, 'R'); PDF::Cell(2, 0); PDF::Cell(10, 0, !$model->technical_proceed?'SI':'', 1, 1, 'C', !$model->technical_proceed?1: 0);
        PDF::SetFont('times', 'B', 12);
        PDF::Ln();
        PDF::Cell(80, 0,'CONCLUSIONES', 0, 1, '', 0, null, 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::MultiCell(0, 25, $model->conclusions, 1, '',0,1);

        $width = PDF::GetPageWidth();
        $width = $width - 30;
        $width = $width/2;

        PDF::SetFont('helvetica', '', 8);
        PDF::SetY(PDF::GetY()+20);
        PDF::Cell($width, 4, 'Sr. Wilber V. Illanes Padilla', 0, 0, 'C');
        PDF::Cell($width, 4, 'Abg. Lourdes Montserrat Cuba Benavente', 0, 1, 'C');
        PDF::SetFont('helvetica', 'B', 8);
        PDF::Cell($width, 4, 'APOYO TECNICO TRAFICO Y VIALIDAD', 0, 0, 'C');
        PDF::Cell($width, 4, 'ASESOR JURIDICO DE TRAFICO Y VIALIDAD', 0, 1, 'C');
        PDF::Cell($width, 4, 'Gobierno Autónomo Municipal de Potosí', 0, 0, 'C');
        PDF::Cell($width, 4, 'Gobierno Autónomo Municipal de Potosí', 0, 1, 'C');


        PDF::SetFont('helvetica', '', 8);
        PDF::SetY(PDF::GetY()+20);
        PDF::Cell(0, 4, 'Ing. Mauricio Aguilar Moreira', 0, 1, 'C');
        PDF::SetFont('helvetica', 'B', 8);
        PDF::Cell(0, 4, 'JEFE TRAFICO Y VIALIDAD', 0, 1, 'C');
        PDF::Cell(0, 4, 'Gobierno Autónomo Municipal de Potosí', 0, 0, 'C');

//        PDF::Ln();
//        PDF::Ln();
//        PDF::Ln();
//        PDF::Ln();
//        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio' , 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
//        PDF::SetY(PDF::GetY()+30);
//        $date = new Carbon($model->created_at);
//        PDF::Cell(0, 0, 'Potosí, '.$date->format('d').' de '.$months[$date->format('n')-1].' del '.$date->format('Y'), 0, 0, 'R');

//        PDF::Ln();
//        PDF::Ln();
//        PDF::Ln();
//        PDF::Ln();
//        PDF::Ln();
//        PDF::SetFont('helvetica', 'B', 8);
//        PDF::Cell(0, 0, 'JEFATURA DE TRAFICO Y VIALIDAD', 0, 1, 'C');
//        PDF::Cell(0, 0, 'GOBIERNO AUTONOMO MUNICIPAL DE POTOSI', 0, 0, 'C');
        PDF::Output('Certificado.pdf');
    }
    public function qr($id){
        $model = Certificate::findOrFail($id);
        PDF::setHeaderCallback(function($pdf){
            $pdf->SetFont('times', 'B', 12);
            $pdf->SetY(10);
            $pdf->MultiCell(0,15, "GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ\nJEFATURA DE TRÁFICO Y VIALIDAD", 1, 'C',0,0);

        });

        PDF::SetPageUnit('mm');
        PDF::SetMargins(15, 30, 15, 15);
        PDF::SetTitle('QR');
        PDF::AddPage('P', 'LETTER');
        PDF::SetFont('helvetica', 'B', 11);

        PDF::Cell(0,0,'QR del solicitante '.$model->solicitante, 0, 1, 'C');
        PDF::Ln();
        PDF::Write2DBarcode(route('certificates.verify', $model->code), 'QRCODE,M', PDF::GetX() + 25, PDF::GetY(), 140, 140);
        PDF::Output('qr.pdf');
    }

    public function booths(){
        PDF::SetPageUnit('mm');
        PDF::SetMargins(15, 15, 15, 15);
        PDF::SetTitle('Certificado');
        PDF::AddPage('P', 'LETTER');

        PDF::SetFont('helvetica', 'BU', 11);
        PDF::Cell(0, 0, 'NOTIFICACIÓN Nro. 122/2024', 0, 1, 'C');

        PDF::SetFont('helvetica', '', 11);
        PDF::WriteHtmlCell(0, 0, '', '', '<b style="text-align: justify">LA JEFATURA DE TRÁFICO Y VIALIDAD DEPENDIENTE DEL GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ</b>', 0, 1);
        PDF::SetY(PDF::GetY()+4);
        PDF::WriteHtmlCell(0, 0, '', '', '<span style="text-align: justify">En cumplimiento de los Artículos 21, 302 inc. 6) de la Constitución Política del Estado, O.M. 111/2005 y las facultades conferidas por Ley 482 Art. 31 inc. a) <b>NOTIFICA A QUIEN CORRESPONDA.</b> Propietaria de la <b>caseta</b> ubicado en:</span>', 0, 1);
        PDF::SetY(PDF::GetY()+6);
        PDF::Cell(0, 0, '............................................................................................', 0, 1, 'C');
        PDF::SetY(PDF::GetY()+2);
        PDF::WriteHtmlCell(0, 0, '', '', '<span style="text-align: justify">Para que el mismo pueda presentarse en dependencia de la Jefatura de Tráfico y Vialidad, en el plazo de 24 hrs. a efectos de presentar toda la documentacion pertinente que autoriza la intervención de acometida o ruptura de aceras, calles, avenidas y otros bienes de dominio municipal. En caso de no presentarse y omitir esta notificación se procederá a las acciones legales pertinentes, sean estas Administrativas, Civiles o Penales; Cabe aclarar que la Destrucción o Deterioro de Bienes del Estado (Art. 223 C.P.E.) está sancionado por ley y deberemos proceder a la remisión de antecedentes al Ministerio Público.</span>', 0, 1);
        PDF::SetY(PDF::GetY()+4);
        PDF::Cell(0, 0, 'Potosí, 30 de Enero del 2023', 0, 1, 'C');
        PDF::SetFont('helvetica', 'B', 11);
        PDF::Cell(0, 0, 'JEFATURA DE TRÁFICO Y VIALIDAD', 0, 1, 'C');
        PDF::Output('NotificacionCaseta.pdf');
    }

    public function ways(){
        PDF::SetPageUnit('mm');
        $margin = 15;
        PDF::SetMargins($margin, $margin, $margin, $margin);
        PDF::SetTitle('Certificado');
        PDF::AddPage('P', 'LETTER');

        PDF::SetFont('helvetica', 'BU', 11);
        PDF::Cell(0, 0, 'NOTIFICACIÓN Nro. 122/2024', 0, 1, 'C');

        PDF::SetFont('helvetica', '', 11);
        PDF::WriteHtmlCell(0, 0, '', '', '<b style="text-align: justify">LA JEFATURA DE TRÁFICO Y VIALIDAD DEPENDIENTE DEL GOBIERNO AUTÓNOMO MUNICIPAL DE POTOSÍ</b>', 0, 1);
        PDF::SetY(PDF::GetY()+4);
        PDF::WriteHtmlCell(0, 0, '', '', '<span style="text-align: justify">En cumplimiento de los Artículos 21, 302 inc. 6) de la Constitución Política del Estado, O.M. 111/2005 y las facultades conferidas por la Ley 481 Art. 31 inc. a) <b>NOTIFICA A QUIEN CORRESPONDA</b>. Propietario(a) del bien inmueble, actividad económica y/o vehículo</span>', 0, 1);
        PDF::SetY(PDF::GetY()+4);
        $y = PDF::GetY();
        $pageWidth = PDF::GetPageWidth() - ($margin * 2);
        $col = $pageWidth / 2;
        PDF::WriteHtmlCell($col, 0, '', '', '<span style="text-align: justify"><b>OBSTRUCCIÓN DE VÍA</b> ubicado en:</span>', 0, 1);
        PDF::SetY(PDF::GetY()+8);
        PDF::Cell($col, 0, '.....................................................................', 0, 1, 'C');

        PDF::SetXY($col+$margin+20, $y);
        PDF::SetFont('helvetica', '', 7);
        PDF::Cell(60, 0, 'Agregados y escombros en la acera o vía', 0, 0);
        PDF::Cell(7, 3, '', 1, 1);
        PDF::SetXY($col+$margin+20, PDF::GetY() + 2);
        PDF::Cell(60, 0, 'Chapista o auto abandonado', 0, 0);
        PDF::Cell(7, 3, '', 1, 1);
        PDF::SetXY($col+$margin+20, PDF::GetY() + 2);
        PDF::Cell(60, 0, 'Uso de calaminas en acera o vía sin autorización', 0, 0);
        PDF::Cell(7, 3, '', 1, 1);
        PDF::SetXY($col+$margin+20, PDF::GetY() + 2);
        PDF::Cell(60, 0, 'Uso de andamios en acera o vía sin autorización', 0, 0);
        PDF::Cell(7, 3, '', 1, 1);

        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 11);
        PDF::WriteHtmlCell(0, 0, '', '', '<span style="text-align: justify">Para que el mismo pueda presentarse en dependencia de la Jefatura de Tráfico y Vialidad, en el plazo de 24 hrs. Ubicado en la calle Salguero Complejo Deportivo "Las Delicias", a efectos de presentar toda la documentacion pertinente que autoriza la intervención de acometidas, ruptura y obstrucción de aceras, calles y otros bienes de dominio municipal. En caso de no presentarse y omitir esta notificación se procederá a las acciones legales pertinentes, sean estas Administrativas, Civiles o Penales; Cabe aclarar que la Destrucción o Deterioro de Bienes del Estado (Art. 223 C.P.E.) está sancionado por ley y procederemos a la remisión de antecedentes al Ministerio Público.</span>', 0, 1);
        PDF::SetY(PDF::GetY()+4);
        PDF::Cell(0, 0, 'Potosí, 30 de Enero del 2023', 0, 1, 'C');
        PDF::SetFont('helvetica', 'B', 11);
        PDF::Cell(0, 0, 'JEFATURA DE TRÁFICO Y VIALIDAD', 0, 1, 'C');
        PDF::Output('NotificacionVias.pdf');
    }
}

