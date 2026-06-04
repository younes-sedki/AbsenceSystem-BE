<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnseignantController;
use App\Http\Controllers\Api\V1\EtudiantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::middleware('role:etudiant')->prefix('etudiant')->group(function () {
            Route::get('/absences', [EtudiantController::class, 'absences']);
            Route::post('/absences/{absence}/justifier', [EtudiantController::class, 'justifier']);
        });

        Route::middleware('role:enseignant')->prefix('enseignant')->group(function () {
            Route::get('/assignments', [EnseignantController::class, 'assignments']);
            Route::get('/classes/{classeCode}/etudiants', [EnseignantController::class, 'etudiants']);
            Route::post('/sessions', [EnseignantController::class, 'storeSession']);
            Route::post('/sessions/{session}/soumettre', [EnseignantController::class, 'soumettre']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/absences', [AdminController::class, 'absences']);
            Route::get('/justifications', [AdminController::class, 'justifications']);
            Route::post('/justifications/{justification}/accepter', [AdminController::class, 'accepter']);
            Route::post('/justifications/{justification}/rejeter', [AdminController::class, 'rejeter']);
        });
    });
});
