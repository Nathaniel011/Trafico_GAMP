<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Auth::routes(['register' => false]);

//Route::get('/', 'HomeController@index')->name('home.index');
Route::group(['prefix' => 'reports' ], function(){
    Route::get('shops', 'ReportController@shops')->name('reports.shops');
});

Route::get('shops', 'CertificateController@shops')->name('shops');

Route::group(['prefix'=>'certificate'], function(){
    Route::get('/', 'CertificateController@index')->name('certificate.index');
    Route::get('data', 'CertificateController@data')->name('certificate.data');
    Route::get('create', 'CertificateController@create')->name('certificate.create');
    Route::post('create', 'CertificateController@store')->name('certificate.store');
    Route::get('edit/{id}', 'CertificateController@legalEdit')->name('certificate.legal.edit');
    Route::post('edit/{id}', 'CertificateController@update')->name('certificate.legal.update');
    Route::get('pdf/{id}', 'CertificateController@pdf')->name('certificate.pdf');
    Route::get('show/{id}', 'CertificateController@show')->name('certificate.show');
    Route::get('showqr/{id}', 'CertificateController@showqr')->name('certificate.showqr');
    Route::get('qr/{id}', 'CertificateController@qr')->name('certificate.qr');
    Route::get('showmap/{id}', 'CertificateController@showmap')->name('certificate.showmap');
    Route::get('technical/{id}', 'CertificateController@technical')->name('certificate.technical');
    Route::post('technical/{id}', 'CertificateController@technicalStore')->name('certificate.technical.store');
    Route::get('pdf-legal/{id}', 'CertificateController@pdf_legal')->name('certificate.pdf.legal');
    Route::get('showinspectioncert/{id}', 'CertificateController@showInspectionCert')->name('certificate.showInspectionCert');
    Route::get('pdfinspectioncert/{id}', 'CertificateController@pdfInspectionCert')->name('certificate.pdf.inspectionCert');



});



Route::get('/admin', 'HomeController@index')->name('home.index');
Route::get('notfound','HomeController@notFound');
Route::get('/Search/{id}', 'VerifyController@search');
Route::get('/check/{id}', 'VerifyController@show')->name('certificates.verify');

Route::group(['prefix'=>'carparking'], function(){
    Route::get('/','CarParkingController@index')->name('carparking.index');
    Route::get('data', 'CarParkingController@data')->name('carparking.data');
    Route::get('create', 'CarParkingController@create')->name('carparking.create');
    Route::post('create', 'CarParkingController@store')->name('carparking.store');
    Route::get('edit/{id}', 'CarParkingController@edit')->name('carparking.edit');
    Route::post('edit/{id}', 'CarParkingController@update')->name('carparking.update');
    Route::get('points', 'CarParkingController@points')->name('carparking.points');
});

Route::group(['prefix'=>'motoparking'], function(){
    Route::get('/','MotoParkingController@index')->name('motoparking.index');
    Route::get('data', 'MotoParkingController@data')->name('motoparking.data');
    Route::get('create', 'MotoParkingController@create')->name('motoparking.create');
    Route::post('create', 'MotoParkingController@store')->name('motoparking.store');
    Route::get('edit/{id}', 'MotoParkingController@edit')->name('motoparking.edit');
    Route::post('edit/{id}', 'MotoParkingController@update')->name('motoparking.update');
    Route::get('points', 'MotoParkingController@points')->name('motoparking.points');
});

Route::group(['prefix'=>'notifications'], function(){
    Route::get('/', 'NotificationController@index')->name('notifications.index');
    Route::get('create', 'NotificationController@create')->name('notifications.create');
    Route::post('create', 'NotificationController@store')->name('notifications.store');
    Route::get('{id}/edit', 'NotificationController@edit')->name('notifications.edit');
    Route::post('{id}/edit', 'NotificationController@update')->name('notifications.update');
    Route::get('{id}', 'NotificationController@show')->name('notifications.show');
    Route::post('{id}', 'NotificationController@delete')->name('notifications.delete');
});

Route::group(['prefix'=>'groups'], function(){
    Route::get('/', 'GroupController@index')->name('groups.index');
    Route::get('create', 'GroupController@create')->name('groups.create');
    Route::post('create', 'GroupController@store')->name('groups.store');
    Route::get('{id}/edit', 'GroupController@edit')->name('groups.edit');
    Route::post('{id}/edit', 'GroupController@update')->name('groups.update');
    Route::get('{id}', 'GroupController@show')->name('groups.show');
});

Route::group(['prefix'=>'complaints'], function(){
    Route::get('/', 'ComplaintController@index')->name('complaints.index');
    Route::get('{id}', 'ComplaintController@show')->name('complaints.show');
    Route::post('{id}/state', 'ComplaintController@setState')->name('complaints.state');
});



Route::get('/', 'VerifyController@parkings');

Route::group(['prefix'=>'inspectors'], function(){
    Route::get('/', 'InspectorController@index')->name('inspectors.index');
    Route::get('create', 'InspectorController@create')->name('inspectors.create');
    Route::post('create', 'InspectorController@store')->name('inspectors.store');
    Route::get('{id}/edit', 'InspectorController@edit')->name('inspectors.edit');
    Route::post('{id}/edit', 'InspectorController@update')->name('inspectors.update');
    Route::get('{id}', 'InspectorController@show')->name('inspectors.show');
});

Route::group(['prefix'=>'public'], function(){
    Route::get('parking', 'VerifyController@parkings')->name('public.parking');
    Route::get('carpoints', 'VerifyController@carpoints');
    Route::get('motopoints', 'VerifyController@motopoints');
});


Route::group(['prefix'=>'notices'], function(){
    Route::get('/', 'NoticeController@index')->name('notices.index');
    Route::get('create', 'NoticeController@create')->name('notices.create');
    Route::post('create', 'NoticeController@store')->name('notices.store');
    Route::post('{id}/edit', 'NoticeController@update')->name('notices.update');
    Route::get('{id}', 'NoticeController@show')->name('notices.show');

});

Route::post('upload', 'FileController@upload')->middleware(['auth', 'can:field-work'])->name('upload');

Route::post('certificate/map-capture/{id}', 'CertificateController@mapCapture')->middleware('throttle:20,1')->name('certificate.map-capture');
Route::get('casetas', 'CasetaController@index')->name('casetas.index');
Route::get('casetas/mapa', 'CasetaController@map')->name('casetas.map');
Route::get('casetas/puntos', 'CasetaController@points')->name('casetas.points');
Route::get('casetas/nueva', 'CasetaController@create')->name('casetas.create');
Route::post('casetas', 'CasetaController@store')->name('casetas.store');
Route::get('casetas/{caseta}/editar', 'CasetaController@edit')->name('casetas.edit');
Route::post('casetas/{caseta}/editar', 'CasetaController@update')->name('casetas.update');
Route::post('casetas/{caseta}/licencias', 'CasetaController@license')->name('casetas.license');
Route::post('casetas/{caseta}/procedimientos/{case}/cerrar', 'CasetaController@closeCase')->name('casetas.case.close');
Route::post('casetas/{caseta}/vincular-certificado', 'CasetaController@linkCertificate')->middleware(\App\Http\Middleware\RequireFeature::class.':caseta_dossier')->name('casetas.link.certificate');
Route::post('casetas/{caseta}/vincular-aviso', 'CasetaController@linkNotice')->middleware(\App\Http\Middleware\RequireFeature::class.':caseta_dossier')->name('casetas.link.notice');
Route::get('casetas/documentos-historicos', 'CasetaLegacyController@index')->middleware(\App\Http\Middleware\RequireFeature::class.':caseta_dossier')->name('casetas.legacy');
Route::post('casetas/documentos-historicos/vincular', 'CasetaLegacyController@link')->middleware(\App\Http\Middleware\RequireFeature::class.':caseta_dossier')->name('casetas.legacy.link');
Route::get('casetas/{caseta}', 'CasetaController@show')->name('casetas.show');
Route::post('notice-photos', 'NoticePhotoController@store')->middleware('throttle:30,1')->name('notice-photos.store');
Route::get('notice-photos/temporary/{photo}', 'NoticePhotoController@preview')->name('notice-photos.preview');
Route::get('notice-photos/{photo}', 'NoticePhotoController@show')->name('notice-photos.show');
Route::get('perfiles', 'UserAccessController@index')->name('user-access.index');
Route::get('perfiles/crear', 'UserAccessController@create')->name('user-access.create');
Route::post('perfiles', 'UserAccessController@store')->name('user-access.store');
Route::get('perfiles/{user}/editar', 'UserAccessController@edit')->name('user-access.edit');
Route::post('perfiles/{user}', 'UserAccessController@update')->name('user-access.update');
Route::get('perfiles/{user}/restablecer-contrasena', 'UserAccessController@resetForm')->name('user-access.reset-form');
Route::post('perfiles/{user}/restablecer-contrasena', 'UserAccessController@resetPassword')->middleware('throttle:10,1')->name('user-access.reset-password');
Route::get('mi-cuenta/contrasena', 'AccountPasswordController@edit')->name('account-password.edit');
Route::post('mi-cuenta/contrasena', 'AccountPasswordController@update')->middleware('throttle:10,1')->name('account-password.update');

Route::get('certificate/history/{id}', 'CertificateController@history')->middleware(\App\Http\Middleware\RequireFeature::class.':caseta_dossier')->name('certificate.history');
Route::get('recaudaciones', 'RecaudacionesController@index')->middleware(\App\Http\Middleware\RequireFeature::class.':recaudaciones')->name('recaudaciones.index');
Route::get('recaudaciones/plantilla', 'RecaudacionesController@template')->middleware(\App\Http\Middleware\RequireFeature::class.':recaudaciones')->name('recaudaciones.template');
Route::post('recaudaciones/revisar', 'RecaudacionesController@preview')->middleware(\App\Http\Middleware\RequireFeature::class.':recaudaciones')->name('recaudaciones.preview');
Route::post('recaudaciones/importar', 'RecaudacionesController@import')->middleware(\App\Http\Middleware\RequireFeature::class.':recaudaciones')->name('recaudaciones.import');
Route::post('casetas/{caseta}/licencias/{license}/verificar-pago', 'RecaudacionesController@store')->middleware(\App\Http\Middleware\RequireFeature::class.':recaudaciones')->name('recaudaciones.store');
