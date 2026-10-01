<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - TaskFlow Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health check endpoint
    Route::get('/health', HealthController::class);

    // Protected routes placeholder
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', function (Request $request) {
            return $request->user();
        });
    });
});
