<?php

use App\Http\Controllers\ParkingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// Route::get('/', [ParkingController::class, 'index']);
// Route::post('/', [ParkingController::class, 'create']);

// Route::post('/check-plate', [ParkingController::class, 'check']);
// Route::prefix('api')
//     ->middleware('api')
//     ->group(function () {
//         Route::get('/parking', [ParkingController::class, 'index']);
//         Route::post('/parking', [ParkingController::class, 'create']);
//         Route::post('/check-plate', [ParkingController::class, 'check']);
//     });