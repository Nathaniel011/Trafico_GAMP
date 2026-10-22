<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('notifications', 'NotificationController@apiIndex');
Route::get('groups', 'GroupController@apiIndex');
Route::get('categories', 'CategoryController@apiIndex');
Route::get('routes/{routeId}/path', 'RouteController@apiPath');

Route::group(['prefix'=> 'complaints', 'middleware'=>'auth:sanctum'], function(){
    Route::get('/', 'ComplaintController@apiIndex');
    Route::post('/', 'ComplaintController@store');
});

Route::post('auth/login', 'InspectorController@apiLogin')->middleware('throttle:10,1');
Route::group(['prefix'=> 'auth', 'middleware'=>'auth:sanctum'], function(){
    Route::get('profile', 'InspectorController@apiProfile');
    Route::get('logout', 'InspectorController@apiLogout');
});
