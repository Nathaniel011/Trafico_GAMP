<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

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
        $this->middleware('can:field-work')->only(['technical', 'technicalStore']);
        $this->middleware('can:edit-certificates')->only(['create', 'store', 'legalEdit', 'edit', 'update']);
    }

    public function index(){
        $certificates = collect();

        return view('certificate.index', [
            'certificates' => $certificates
        ]);
    }

    public function shops(){
        return redirect()->route('casetas.map');
    }

    public function data(){
        return DataTables::of(Certificate::query())
            ->addColumn('estado', function ($cert) {
                if (!$cert->legal_proceed) {
                    return "<span class='badge badge-secondary'>Improcedente Legal</span>";
                } elseif ($cert->legal_proceed && $cert->inspection_date == null) {
                    return "<span class='badge badge-warning'>Procedente Legal</span>";
                } elseif ($cert->legal_proceed && $cert->inspection_date && $cert->technical_proceed) {
                    return "<span class='badge badge-success'>Finalizado (procedente tecnico)</span>";
                } elseif ($cert->legal_proceed && $cert->inspection_date && !$cert->technical_proceed) {
                    return "<span class='badge badge-danger'>Finalizado (improcedente tecnico)</span>";
                }
            })
            ->addColumn('actions', function ($cert) {
                $showRoute = route('certificate.show', $cert->id);
                $showqrRoute = route('certificate.showqr', $cert->id);
                $showInspectionCert = route('certificate.showInspectionCert', $cert->id);

                $btnEdit = '';
                $btnTechnical = '';
                $btnInspectionCert = '';
                $btnShow = "<a href='$showRoute' class='btn btn-xs btn-primary'><i class='fa fa-eye'></i> "
                . ($cert->inspection_date ? ' Informe técnico' : 'Mostrar')
                . "</a>";
                $btnQR = "<a href='$showqrRoute' class='btn btn-xs btn-primary'><i class='fa fa-eye'></i> QR</a>";
                if ($cert->legal_proceed && $cert->inspection_date && $cert->technical_proceed) {
                    $btnInspectionCert = "<a href='$showInspectionCert' class='btn btn-xs btn-primary'><i class='fa fa-eye'></i> Informe certificación de inspeccion </a>";
                }

                if(auth()->user()->can('edit-certificates')) {
                    if ($cert->inspection_date == null) {
                        $legalEditRoute = route('certificate.legal.edit', $cert->id);
                        $btnEdit = "<a href='$legalEditRoute' class='btn btn-xs btn-warning'><i class='fa fa-edit'></i> Editar legal</a>";
                    }

                }
                if (auth()->user()->can('field-work')) {
                    if ($cert->legal_proceed) {
                        $label = $cert->inspection_date == null ? 'Completar técnico' : 'Editar técnico';
                        $btnClass = $cert->inspection_date == null ? 'btn-success' : 'btn-info';

                        $technicalRoute = route('certificate.technical', $cert->id);
                        $btnTechnical = "<a href='$technicalRoute' class='btn btn-xs $btnClass'><i class='fa fa-wrench'></i> $label</a>";
                    }
                }

                return "$btnEdit $btnTechnical $btnShow $btnInspectionCert $btnQR";
            })
            ->editColumn('created_at', function($cert) {
                return $cert->created_at->format('Y-m-d H:i:s');
            })
            ->rawColumns(['actions', 'estado'])
            ->make(true);
    }
    public function create(Request $request){
        $model = new Certificate();
        if ($request->filled('caseta_id')) {
            abort_unless(\App\Services\SchemaCompatibility::casetaControlAvailable(), 422, 'Esta instalación usa certificados independientes, sin ficha de caseta.');
            $caseta = \App\Models\Caseta::findOrFail($request->caseta_id);
            $model->caseta_id = $caseta->id;
            $model->solicitante = $caseta->owner;
            $model->ci = $caseta->document;
            $model->celular = $caseta->phone;
        }
        return view('certificate.create', compact('model'));
    }

    private function legalRules(): array
    {
        return [
            'type' => 'required|string|max:100', 'mode' => 'required|string|max:100',
            'another_type' => 'required_if:type,OTROS|nullable|string|max:100',
            'solicitante' => 'required|string|max:255', 'ci' => 'required|string|max:80',
            'celular' => 'nullable|string|max:80', 'rubro' => 'required|string|max:255',
            'legal_proceed' => 'required|boolean',
            'conclusions' => 'required_if:legal_proceed,0|nullable|string|max:10000',
            'caseta_id' => \App\Services\SchemaCompatibility::casetaControlAvailable() ? 'nullable|integer|exists:casetas,id' : 'prohibited',
        ];
    }

    private function fillLegal(Certificate $model, Request $request): void
    {
        $model->tipo = mb_strtoupper($request->type === 'OTROS' ? $request->another_type : $request->type);
        $model->modalidad = $request->mode;
        $model->solicitante = mb_strtoupper($request->solicitante);
        $model->ci = mb_strtoupper($request->ci);
        $model->celular = mb_strtoupper((string) $request->celular);
        $model->rubro = mb_strtoupper($request->rubro);
        $model->legal_proceed = $request->boolean('legal_proceed');
        $model->conclusions = $request->conclusions ?: ' ';
    }

    public function store(Request $request)
    {
        $request->validate($this->legalRules());
        $model = new Certificate();
        $this->fillLegal($model, $request);
        $model->inspection_date = null;
        $model->direccion = '';
        $model->dim_ancho = 0;
        $model->dim_largo = 0;
        $model->color = '';
        $model->material = '';
        $model->tipo_via = 'ACERA';
        $model->dim_via = 0;
        $model->dim_perc_ocupado = null;
        $model->technical_proceed = true;
        $model->ref_product = '';
        $model->ref_turn = '';
        $model->ref_start = '00:00';
        $model->ref_end = '00:00';
        $model->lat = 0;
        $model->lng = 0;
        $model->code = (string) \Illuminate\Support\Str::uuid();
        if ($request->filled('caseta_id')) {
            $caseta = \App\Models\Caseta::findOrFail($request->caseta_id);
            $model->caseta_id = $caseta->id;
            // Copy a registered reference without treating the legal stage as a GPS capture.
            foreach (['lat', 'lng', 'accuracy', 'located_at', 'location_reason'] as $field) $model->$field = $caseta->$field;
            $model->location_source = 'EXISTING';
            $model->direccion = $caseta->address;
        }
        $model->save();
        return redirect()->route('certificate.index')->with('success', 'Certificación legal registrada. Complete la etapa técnica cuando sea procedente.');
    }

    public function technical($id)
    {
        $model = Certificate::findOrFail($id);
        if (!$model->legal_proceed) {
            return redirect()->route('certificate.index')->with('error', 'Este certificado no fue aprobado legalmente.');
        }
        $location = ($model->inspection_date || $model->caseta_id) ? $model : (object) ['lat' => null, 'lng' => null];
        return view('certificate.technical', compact('model', 'location'));
    }

    public function technicalStore(Request $request, $id)
    {
        $request->validate([
            'certificate_version' => 'required|string|size:64',
            'revision_reason' => (config('features.caseta_dossier') ? 'required' : 'nullable').'|string|max:2000',
            'direccion' => 'required|string|max:255', 'dim_ancho' => 'required|numeric|min:0',
            'dim_largo' => 'required|numeric|min:0', 'tipo_via' => 'required|string|max:100',
            'dim_via' => 'required|numeric|gt:0', 'dim_perc_ocupado' => 'nullable|numeric|min:0',
            'ref_product' => 'required|string|max:255', 'ref_other_product' => 'required_if:ref_product,OTROS (DESCRIBIR)|nullable|string|max:255',
            'ref_turn' => 'required|string|max:100', 'ref_start' => 'required|date_format:H:i', 'ref_end' => 'required|date_format:H:i',
            'color' => 'required|string|max:100', 'material' => 'required|string|max:255',
            'technical_proceed' => 'required|boolean', 'inspection_date' => 'required|date_format:d/m/Y',
            'conclusions' => 'nullable|string|max:10000',
        ] + \App\Services\LocationRules::rules(true));
        $model = Certificate::findOrFail($id);
        abort_unless($model->legal_proceed, 422, 'La solicitud debe ser procedente en la etapa legal.');
        $original = clone $model;
        if ($request->location_source === 'EXISTING' && !$model->inspection_date && !$model->caseta_id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['location_source' => 'La etapa legal no tiene una ubicación confirmada. Capture el GPS o indique una ubicación manual.']);
        }
        \App\Services\LocationRules::verifyExisting($request, $original);
        $model->inspection_date = Carbon::createFromFormat('!d/m/Y', $request->inspection_date)->format('Y-m-d');
        foreach (['direccion', 'color', 'material', 'ref_turn'] as $field) $model->$field = mb_strtoupper($request->$field);
        foreach (['lat', 'lng', 'dim_ancho', 'dim_largo', 'tipo_via', 'dim_via', 'ref_start', 'ref_end'] as $field) $model->$field = $request->$field;
        $model->dim_perc_ocupado = round(((float) $request->dim_ancho * 100) / (float) $request->dim_via, 2);
        $model->ref_product = mb_strtoupper($request->ref_product === 'OTROS (DESCRIBIR)' ? $request->ref_other_product : $request->ref_product);
        $model->technical_proceed = $request->boolean('technical_proceed');
        $name = $model->solicitante;
        $document = $model->ci;
        $type = $model->modalidad === 'NUEVO' ? 'OTORGACIÓN' : $model->modalidad;
        if ($model->technical_proceed) {
            $text = "REALIZADA LA INSPECCIÓN DE LA CASETA DEL(DE LA) SR(A). $name, CI: $document, EN CUANTO A SUS DIMENSIONES SE ENCUENTRA DENTRO DE LO DETERMINADO SEGÚN DECRETO EDIL N° 014/2015 Y SU MODIFICATORIO, EN CONSECUENCIA, BAJO ESTOS ANTECEDENTES SEÑALADOS, ES VIABLE LA PROSECUCIÓN DEL TRAMITE PARA LA $type DE LICENCIA DE FUNCIONAMIENTO, ADEMAS QUE LA UBICACIÓN DE LA CASETA SE ENCUENTRA EN UN LUGAR ACCESIBLE QUE NO PERJUDICA EL TRABAJO PEATONAL NI VEHICULAR.";
        } else {
            $text = "REALIZADA LA INSPECCIÓN DE LA CASETA DEL(DE LA) SR(A). $name, CI: $document, EN CUANTO A SUS DIMENSIONES NO SE ENCUENTRA DENTRO DE LO DETERMINADO SEGÚN DECRETO EDIL N° 014/2015 Y SU MODIFICATORIO, EN CONSECUENCIA, BAJO ESTOS ANTECEDENTES SEÑALADOS, NO ES VIABLE LA PROSECUCIÓN DEL TRAMITE PARA LA $type DE LICENCIA DE FUNCIONAMIENTO.";
        }
        $observations = (string) $request->conclusions;
        if (str_starts_with($observations, 'REALIZADA LA INSPECCIÓN')) $observations = explode("\n\n", $observations, 2)[1] ?? '';
        $model->conclusions = $text."\n\n".$observations;
        $model->location_source = $request->location_source;
        $model->accuracy = $request->location_source === 'EXISTING' ? $original->accuracy : ($request->location_source === 'GPS' ? $request->accuracy : null);
        $model->located_at = $request->location_source === 'EXISTING' ? $original->located_at : ($request->location_source === 'GPS' ? Carbon::parse($request->located_at)->utc() : now('UTC'));
        $model->location_reason = $request->location_source === 'EXISTING' ? $original->location_reason : $request->location_reason;
        \App\Services\CertificateHistory::save($model, $request->certificate_version, $request->revision_reason);
        return redirect()->route('certificate.show', $model->id)->with('success', 'Datos técnicos guardados correctamente.');
    }

    public function legalEdit($id)
    {
        $model = Certificate::findOrFail($id);
        if ($model->inspection_date !== null) {
            return redirect()->route('certificate.index')->with('error', 'No se pueden editar los datos legales después de completar la etapa técnica.');
        }
        return view('certificate.legal_edit', compact('model'));
    }

    public function edit($id) { return $this->legalEdit($id); }

    public function update(Request $request, $id)
    {
        $request->validate($this->legalRules() + ['certificate_version' => 'required|string|size:64']);
        $model = Certificate::findOrFail($id);
        abort_if($model->inspection_date !== null, 422, 'La etapa técnica ya fue completada; no se pueden modificar los datos legales.');
        $this->fillLegal($model, $request);
        \App\Services\CertificateHistory::save($model, $request->certificate_version, $request->revision_reason);
        return redirect()->route('certificate.show', $model->id);
    }

    public function history($id)
    {
        abort_unless(\App\Services\SchemaCompatibility::certificateHistoryAvailable(), 503, 'El historial de revisiones no está disponible en esta instalación.');
        $model = Certificate::findOrFail($id);
        $revisions = $model->revisions()->paginate(20);
        return view('certificate.history', compact('model', 'revisions'));
    }
    public function show($id) { return view('certificate.show', ['model' => Certificate::findOrFail($id)]); }
    public function showInspectionCert($id) { return view('certificate.showInspectionCert', ['model' => Certificate::findOrFail($id)]); }
    public function showqr($id) { return view('certificate.showqr', ['model' => Certificate::findOrFail($id)]); }
    public function showmap($id) { return view('certificate.showmap', ['model' => Certificate::findOrFail($id)]); }

    public function pdf_legal($id){
        PDF::reset();
        $model = Certificate::findOrFail($id);

        PDF::SetPageUnit('mm');
        PDF::SetMargins(20, 20, 20);
        PDF::AddPage('P', 'LETTER');
        PDF::SetFont('times', 'B', 12);
        PDF::SetTitle('Decreto');

        PDF::Cell(0, 10, 'DECRETO', 0, 1, 'C');

        PDF::Ln(5);

        $text = '
        Que habiéndose revisado la solicitud renovación de licencia de funcionamiento presentado por la parte impetrante Sr. <b>' . e(mb_strtoupper($model->solicitante)) . '</b>, se puede advertir que la misma
        <b>' . ($model->legal_proceed ? 'ha cumplido con todos los requisitos exigidos' : 'NO ha cumplido con todos los requisitos exigidos') . '</b>
        en la normativa municipal vigente para que se pueda dar viabilidad a la inspección in situ de la caseta en cuestión.'
        . ($model->legal_proceed ? ' Señalando día y hora para tal efecto. ......................... de '.now('America/La_Paz')->year.' a horas .........................' : '');
        PDF::SetFont('times', '', 12);
        PDF::writeHTMLCell(0, 0, '', '', $text, 0, 1, false, true, 'L');

        PDF::Ln(6);

        PDF::SetFont('times', '', 12);
        PDF::MultiCell(0, 0, $model->conclusions, 0, '',0,1);

        PDF::Ln(8);

        PDF::SetFont('times', 'B', 12);
        PDF::Cell(0, 10, 'NOTIFÍQUESE', 0, 1);

        PDF::Ln(10);

        PDF::SetFont('times', 'B', 12);
        PDF::Cell(0, 10, 'Potosí, ' . \Carbon\Carbon::parse($model->created_at)->translatedFormat('d') . ' de ' . \Carbon\Carbon::parse($model->created_at)->translatedFormat('F') . ' de ' . \Carbon\Carbon::parse($model->created_at)->translatedFormat('Y'), 0, 1, 'R');

        return response(PDF::Output('Certificado_Legal.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Certificado_Legal.pdf"']);
    }

    public function pdf($id){
        PDF::reset();
        $model = Certificate::findOrFail($id);
        if (!$model->inspection_date) return $this->pdf_legal($id);

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
            PDF::Cell(70, 5, $model->tipo, 0, 1, 'C', 1, '', 1);
        }

        PDF::Ln();
        PDF::SetFont('times', '', 12);
        PDF::Cell(60,0,'SOLICITANTE', 0, 1);

        PDF::SetY(PDF::GetY()+2);
        PDF::SetFont('helvetica', '', 10);
        PDF::Cell(30, 0);
        PDF::Cell(25, 0, 'NOMBRE'); PDF::Cell(95, 0, $model->solicitante, 0, 1, '', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(35, 0);
        PDF::Cell(20, 0, 'C.I.'); PDF::Cell(30, 0, $model->ci, 0, 0, '', 1, '', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'CELULAR'); PDF::Cell(30, 0, $model->celular, 0, 1, '', 1, '', 1);

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
        PDF::Cell(25, 0, 'COLOR'); PDF::Cell(30, 0, $model->color, 0, 0, 'C', 1, '', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'MATERIAL'); PDF::Cell(30, 0, $model->material, 0, 1, 'C', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(30, 0);
        PDF::Cell(25, 0, 'ANCHO'); PDF::Cell(30, 0, $model->dim_ancho, 0, 0, 'C', 1, '', 1);
        PDF::Cell(10, 0, '');
        PDF::Cell(25, 0, 'LARGO'); PDF::Cell(30, 0, $model->dim_largo, 0, 1, 'C', 1, '', 1);
        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        PDF::Cell(80, 0,'DIMENSIÓN DE VÍA PEATONAL', 0, 1, '', 0, null, 1);
        PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, 'TIPO', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->tipo_via, 0, 1, '', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, 'DIMENSION', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->dim_via, 0, 1, '', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(50, 0, '');PDF::Cell(35, 5, '% OCUPADO', 0, 0, '', null, 0, 1);PDF::Cell(25, 5, $model->dim_perc_ocupado, 0, 1, '', 1, '', 1);
        PDF::Ln();

        PDF::SetFont('times', 'B', 12);
        PDF::Cell(60, 0,'REFERENCIA EDIL', 0, 1);

        PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'PRODUCTO DE VENTA O SERVICIO', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(100, 5, $model->ref_product, 0, 1, 'C', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'TURNO', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(100, 5, $model->ref_turn, 0, 1, 'C', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);
        PDF::Cell(15, 0, '');PDF::Cell(60, 5, 'HORARIO', 0, 0, '', null, 0, 1);
        PDF::Cell(2, 0);
        PDF::Cell(10, 5, 'De', 0, 0, 'C', 0);
        $start = Carbon::parse($model->ref_start);
        PDF::Cell(40, 5, $start->format('H:i'), 0, 0, 'C', 1);

        PDF::Cell(10, 5, 'A', 0, 0, 'C', 0);
        $end = Carbon::parse($model->ref_end);
        PDF::Cell(40, 5, $end->format('H:i'), 0, 1, 'C', 1);
        PDF::Ln();
        PDF::SetFont('times', 'B', 12);
        //PDF::Cell(60, 0,'CROQUIS DE UBICACION', 0, 1);
        //style=feature:poi|visibility:off

        //$margin =
        //    PDF::Image($url, '', '', 100, 50);

        //dd($url);

        PDF::Cell(100, 0,'COORDENADAS DE UBICACIÓN', 0, 0);
        PDF::Cell(60, 0,'CODIGO QR', 0, 1);PDF::SetFont('helvetica', '', 10);
        PDF::SetY(PDF::GetY()+5);
        PDF::Cell(15, 0, '');PDF::Cell(25, 5, 'LATITUD', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(35, 5, $model->lat, 0, 1, 'C', 1, '', 1);
        PDF::SetY(PDF::GetY()+2);
        PDF::Cell(15, 0, '');PDF::Cell(25, 5, 'LONGITUD', 0, 0, '', null, 0, 1);PDF::Cell(2, 0);PDF::Cell(35, 5, $model->lng, 0, 1, 'C', 1, '', 1);
        PDF::SetY(PDF::GetY()+1);

        PDF::Write2DBarcode(route('certificates.verify', $model->code), 'QRCODE,M', PDF::GetX() + 100, PDF::GetY()-15, 40, 40);

        PDF::AddPage();
        //PDF::SetY(PDF::GetY()+40);
        PDF::SetFont('helvetica', 'B', 8.5);
        PDF::MultiCell(0, 0, 'CROQUIS DE UBICACIÓN. SOLICITANTE: '.$model->solicitante, 0, 'C', 0, 1, '', '', true);
        $lat = $model->lat;
        $lng = $model->lng;
        $mapImage = app(\App\Services\CertificateMapCapture::class)->get($model);
        if ($mapImage !== null) {
            $mapWidth = 120;
            $mapHeight = $mapWidth * 400 / 640;
            $mapX = (PDF::GetPageWidth() - $mapWidth) / 2;
            $mapY = PDF::GetY() + 2;
            PDF::Image('@'.$mapImage, $mapX, $mapY, $mapWidth, $mapHeight, 'PNG');
            // Reserve exactly the image height plus separation before any report text.
            PDF::SetY($mapY + $mapHeight + 5);
        } else {
            PDF::MultiCell(0, 12, "Croquis no disponible. Ubicación registrada: $lat, $lng. Abra la ficha del certificado para preparar la captura de Leaflet y OpenStreetMap.", 1, 'C');
            PDF::Ln(5);
        }
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
        PDF::Cell($width, 4, 'Tec. Boris Fernando Montesinos Valda', 0, 0, 'C');
        PDF::Cell($width, 4, 'Abg. Jessica Sariel Arando Coro', 0, 1, 'C');
        PDF::SetFont('helvetica', 'B', 8);
        PDF::Cell($width, 4, 'NOTIFICADOR - TRÁFICO Y VIALIDAD', 0, 0, 'C');
        PDF::Cell($width, 4, 'ASESOR JURIDICO DE TRAFICO Y VIALIDAD', 0, 1, 'C');
        PDF::Cell($width, 4, 'Gobierno Autónomo Municipal de Potosí', 0, 0, 'C');
        PDF::Cell($width, 4, 'Gobierno Autónomo Municipal de Potosí', 0, 1, 'C');


        PDF::SetFont('helvetica', '', 8);
        PDF::SetY(PDF::GetY()+20);
        PDF::Cell(0, 4, 'Ing. Alvaro Julio Duran Arismendi', 0, 1, 'C');
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
        return response(PDF::Output('Certificado.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Certificado.pdf"']);
    }

    public function pdfInspectionCert($id)
{
        PDF::reset();
    $model = Certificate::findOrFail($id);
    $paraNombre = e((string) request('para_nombre'));
    $paraRol = e((string) request('para_rol', 'JEFE DE TRÁFICO Y VIALIDAD – G.A.M.P.'));

    $viaNombre = e((string) request('via_nombre'));
    $viaRol = e((string) request('via_rol', 'ASESOR LEGAL DE TRÁFICO Y VIALIDAD – G.A.M.P.'));

    $deNombre = e((string) request('de_nombre'));
    $deRol = e((string) request('de_rol', 'APOYO TECNICO - TRÁFICO Y VIALIDAD'));


    PDF::SetMargins(30, 25, 25, 25);
    PDF::SetTitle('Informe de certificación de inspección');
    PDF::AddPage('P', 'LETTER');

    // ----- Título -----
    PDF::SetFont('times', 'B', 14);
    PDF::Cell(0, 6, 'INFORME', 0, 1, 'C');
    PDF::Ln(8);

    PDF::SetFont('times', '', 12);

    // ----- PARA -----
    PDF::SetFont('', 'B');
    PDF::Cell(25, 6, 'PARA:', 0, 0, 'L');
    PDF::SetFont('', '');
    PDF::writeHTMLCell(
        0, 0, '', '',
        $paraNombre.'<br><b>'.$paraRol.'</b>',
        0, 1, false, true, 'L'
    );

    PDF::Ln(8);

    // ----- VIA -----
    PDF::SetFont('', 'B');
    PDF::Cell(25, 6, 'VIA:', 0, 0, 'L');
    PDF::SetFont('', '');
    PDF::writeHTMLCell(
        0, 0, '', '',
        $viaNombre.'<br><b>'.$viaRol.'</b>',
        0, 1, false, true, 'L'
    );
    PDF::Ln(8);

    // ----- DE -----
    PDF::SetFont('', 'B');
    PDF::Cell(25, 6, 'DE:', 0, 0, 'L');
    PDF::SetFont('', '');
    PDF::writeHTMLCell(
        0, 0, '', '',
        $deNombre.'<br><b>'.$deRol.'</b>',
        0, 1, false, true, 'L'
    );
    PDF::Ln(8);

    // ----- MOTIVO -----
    PDF::SetFont('', 'B');
    PDF::Cell(25, 6, 'MOTIVO:', 0, 0, 'L');
    PDF::SetFont('', 'B');
    PDF::MultiCell(0, 6, "INFORME DE INSPECCIÓN", 0, 'L');
    PDF::Ln(8);

    // FECHA
    $fecha = now()->translatedFormat('d \d\e F \d\e Y');
    $fechaInspection = Carbon::parse($model->inspection_date)->translatedFormat('d \d\e F \d\e Y');

    PDF::SetFont('', 'B');
    PDF::Cell(25, 6, 'FECHA:', 0, 0, 'L');
    PDF::SetFont('', '');
    PDF::MultiCell(0, 6, $fecha, 0, 'L');
    PDF::Ln(6);

    // Línea de separación
    PDF::Ln(6);
    $lineY = PDF::GetY();
    PDF::SetDrawColor(0, 0, 0); // color negro
    PDF::SetLineWidth(0.3);
    PDF::Line(20, $lineY, PDF::GetPageWidth() - 20, $lineY); // de margen izq. a margen der.
    PDF::Ln(6);


    // ===== ANTECEDENTES =====
    PDF::SetFont('times', 'B', 12);
    PDF::Cell(0, 6, '   1. ANTECEDENTES', 0, 1);
    PDF::Ln(4);
    PDF::SetFont('times', '', 12);
    PDF::MultiCell(0, 6,
        "El departamento de Jurídica perteneciente a la Jefatura de Tráfico y Vialidad del G.A.M.P. " .
        "en el marco de sus funciones viene desarrollando trabajos de notificación e inspección para la emisión de " .
        "Renovación de Licencias de Funcionamiento en base al estricto cumplimiento de las normas vigentes.\n",
        0, 'J'
    );
    PDF::Ln(4);

    // ===== ANÁLISIS =====
    PDF::SetFont('times', 'B', 12);
    PDF::Cell(0, 6, '   2. ANÁLISIS', 0, 1);
    PDF::Ln(4);
    PDF::SetFont('times', '', 12);
    PDF::writeHTMLCell(
        0, 0, '', '',
        'En ese entendido, mediante la Inspección realizada en fecha '.$fechaInspection. ' al tipo '.e($model->tipo).' perteneciente a <b>'.e($model->solicitante).'</b> con <b>CI: '.e($model->ci).'</b>, la cual se encuentra ubicada en <b>'.e($model->direccion).'</b> misma que tiene como rubro <b>'.e($model->rubro).'</b> se pudo evidenciar que presenta las siguientes medidas:<br><br><b>- Largo:  '.$model->dim_largo.' metros</b><br><b>- Ancho:  '.$model->dim_ancho.' metros</b><br><br>La '.e($model->tipo_via).' contempla una medida de <b>'.$model->dim_via.' metros</b>, quedando un espacio para el peatón de <b>'.($model->dim_via - $model->dim_ancho) .' metros</b>.',
        0, 1, false, true, 'J'
    );
    PDF::Ln(4);

    // ===== CONCLUSIONES =====
    PDF::SetFont('times', 'B', 12);
    PDF::Cell(0, 6, '   3. CONCLUSIONES', 0, 1);
    PDF::SetFont('times', '', 12);
    PDF::Ln(4);
    PDF::MultiCell(0, 6,
        "El tipo {$model->tipo} desarrolla actividades correspondientes al rubro registrado en la Licencia de Funcionamiento.",
        0, 'J'
    );
    PDF::Ln(4);

    // ===== RECOMENDACIONES =====
    PDF::SetFont('times', 'B', 12);
    PDF::Cell(0, 6, '   4. RECOMENDACIONES', 0, 1);
    PDF::Ln(4);
    PDF::SetFont('times', '', 12);
    $recomendaciones = "- Se recomienda verificar si las medidas del tipo {$model->tipo} cumplen con lo establecido en la norma.\n" .
                       "- Se recomienda verificar si la ubicación del tipo {$model->tipo} corresponde a lo señalado en la Licencia de Funcionamiento.\n" .
                       "- Se recomienda verificar si el espacio disponible para el libre tránsito del peatón se encuentra comprendido en lo establecido en la norma.\n";
    PDF::MultiCell(0, 6, $recomendaciones, 0, 'J');
    PDF::Ln(6);

    // Texto final
    PDF::MultiCell(0, 6, 'Es cuanto puedo informar para fines consiguientes.', 0, 'L');
    PDF::Ln(18);

    return response(PDF::Output('Informe_Certificacion_Inspeccion.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="Informe_Certificacion_Inspeccion.pdf"']);
}

    public function mapCapture(Request $request, $id)
    {
        $model = Certificate::findOrFail($id);
        $capture = app(\App\Services\CertificateMapCapture::class);
        $request->validate([
            'version' => 'required|string',
            'image' => 'required|file|mimes:png|max:2048|dimensions:width=640,height=400',
        ]);
        abort_unless(hash_equals($capture->version($model), $request->input('version')), 409);
        $image = @imagecreatefromstring($request->file('image')->get());
        abort_unless($image !== false, 422);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        $capture->put($model, $bytes);
        return response()->json(['url' => route('certificate.pdf', $model->id)]);
    }
    public function qr($id){
        PDF::reset();
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

        PDF::Cell(0,0,'QR del solicitante '.$model->solicitante, 0, 1, 'C', false, '', 1);
        PDF::Ln();
        PDF::Write2DBarcode(route('certificates.verify', $model->code), 'QRCODE,M', PDF::GetX() + 25, PDF::GetY(), 140, 140);
        return response(PDF::Output('qr.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="qr.pdf"']);
    }

    public function booths(){
        PDF::reset();
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
        return response(PDF::Output('NotificacionCaseta.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="NotificacionCaseta.pdf"']);
    }

    public function ways(){
        PDF::reset();
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
        return response(PDF::Output('NotificacionVias.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="NotificacionVias.pdf"']);
    }
}
