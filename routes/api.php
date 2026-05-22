<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnseignantController;
use App\Http\Controllers\Api\V1\EtudiantController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/profile/request-change', [ProfileController::class, 'requestChange']);
        Route::get('/profile/requests', [ProfileController::class, 'requests']);
        Route::post('/profile/avatar', [ProfileController::class, 'avatar']);

        Route::post('/support/problems', [SupportController::class, 'store']);

        Route::middleware('role:etudiant')->prefix('etudiant')->group(function () {
            Route::get('/absences', [EtudiantController::class, 'absences']);
            Route::post('/absences/{absence}/justifier', [EtudiantController::class, 'justifier']);
            Route::get('/notifications', [EtudiantController::class, 'notifications']);
        });

        Route::middleware('role:enseignant')->prefix('enseignant')->group(function () {
            Route::get('/assignments', [EnseignantController::class, 'assignments']);
            Route::get('/classes/{classeCode}/etudiants', [EnseignantController::class, 'etudiants']);
            Route::post('/sessions', [EnseignantController::class, 'storeSession']);
            Route::post('/sessions/{session}/soumettre', [EnseignantController::class, 'soumettre']);
            Route::get('/sessions', [EnseignantController::class, 'sessions']);
            Route::get('/sessions/{session}/export', [EnseignantController::class, 'export']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/enseignants', [AdminController::class, 'enseignants']);
            Route::get('/absences', [AdminController::class, 'absences']);
            Route::post('/absences/{absence}/sms-parent', [AdminController::class, 'envoyerSmsParent']);
            Route::get('/justifications', [AdminController::class, 'justifications']);
            Route::post('/justifications/{justification}/accepter', [AdminController::class, 'accepter']);
            Route::post('/justifications/{justification}/rejeter', [AdminController::class, 'rejeter']);
            Route::get('/sms-logs', [AdminController::class, 'smsLogs']);
            Route::get('/profile-requests', [AdminController::class, 'profileRequests']);
            Route::post('/profile-requests/{profileRequest}/approuver', [AdminController::class, 'approuverProfile']);
            Route::post('/profile-requests/{profileRequest}/rejeter', [AdminController::class, 'rejeterProfile']);
            Route::get('/problems', [AdminController::class, 'problems']);
            Route::post('/problems/{problemReport}/resoudre', [AdminController::class, 'resoudreProblem']);
            Route::get('/patterns', [AdminController::class, 'patterns']);
        });
    });
});
