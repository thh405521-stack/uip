<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RefreshTokenController;
use App\Http\Controllers\Api\DataAnalysisDashboardApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [LoginController::class, 'submit']);
        Route::post('/logout', [LogoutController::class, 'handle']);
        Route::post('/refresh-token', [RefreshTokenController::class, 'submit']);
    });

    Route::prefix('data-analysis')->middleware('uip.auth')->group(function () {
        Route::get('/dashboard', [DataAnalysisDashboardApiController::class, 'index']);
    });
});
