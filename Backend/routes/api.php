<?php

use App\Http\Controllers\AdminMetricController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json(['message' => 'Welcome admin']);
    });

    Route::get('/admin/metrics', [AdminMetricController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:admin|doctor'])->group(function () {
    Route::get('/admin-or-doctor', function () {
        return response()->json(['message' => 'Welcome admin or doctor']);
    });

    Route::get('/patients', [PatientController::class, 'index']);
});

// Doctor-only: create + list own prescriptions
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/prescriptions', [PrescriptionController::class, 'index']);
});

// Authenticated (policy-gated): detail + consume
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'pdf']);
    Route::put('/prescriptions/{prescription}/consume', [PrescriptionController::class, 'consume']);
});

// Patient-only: list own prescriptions
Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
    Route::get('/me/prescriptions', [PrescriptionController::class, 'myPrescriptions']);
});
