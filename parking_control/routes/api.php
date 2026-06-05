<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\ParkingHistoryController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/parking', [ParkingController::class, 'index']);
    Route::post('/parking', [ParkingController::class, 'create']);
    Route::put('/parking/{id}', [ParkingController::class, 'update']);
    Route::delete('/parking/{id}', [ParkingController::class, 'destroy']);
    Route::post('/check-plate', [ParkingController::class, 'check']);
    Route::post('/check-exit', [ParkingController::class, 'checkExit']);
    
    // Lịch sử ra vào xe
    Route::prefix('history')->group(function () {
        Route::get('/', [ParkingHistoryController::class, 'getAllHistory'])->name('history.all');
        Route::get('/vehicle/{plateNumber}', [ParkingHistoryController::class, 'getVehicleHistory'])->name('history.vehicle');
        Route::get('/today', [ParkingHistoryController::class, 'getTodayHistory'])->name('history.today');
        Route::get('/range', [ParkingHistoryController::class, 'getHistoryByRange'])->name('history.range');
        Route::get('/statistics', [ParkingHistoryController::class, 'getStatistics'])->name('history.statistics');
        Route::post('/entry', [ParkingHistoryController::class, 'recordEntry'])->name('history.entry');
        Route::post('/exit', [ParkingHistoryController::class, 'recordExit'])->name('history.exit');
    });
});
Route::post('/process-ai-ocr', [ParkingController::class, 'process_ai_ocr']);
Route::post('/process-ai-ocr-exit', [ParkingController::class, 'processAiOcrExit']);