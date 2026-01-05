<?php

use App\Http\Controllers\ParkingController;
use Illuminate\Support\Facades\Route;

Route::get('/parking', [ParkingController::class, 'index']);
Route::post('/parking', [ParkingController::class, 'create']);
Route::put('/parking/{id}', [ParkingController::class, 'update']);
Route::delete('/parking/{id}', [ParkingController::class, 'destroy']);
Route::post('/check-plate', [ParkingController::class, 'check']);
Route::post('/process-ai-ocr', [ParkingController::class, 'process_ai_ocr']);