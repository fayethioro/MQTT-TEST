<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\DriverLocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/drivers/location', [DriverLocationController::class, 'updateMulti']);
Route::post('/send-whatsapp', [WhatsAppController::class, 'send']);


Route::post('/drivers/location/sans-mqtt', [DriverLocationController::class, 'updateSansMqtt']);
Route::get('/drivers', [DriverLocationController::class, 'index']);
