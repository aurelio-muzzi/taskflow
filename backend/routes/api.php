<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectMemberController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - TaskFlow Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', HealthController::class);

    // Rotas públicas de Autenticação
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Rotas autenticadas via Laravel Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        // Gestão de sessão e perfil do próprio usuário
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::put('/change-password', [AuthController::class, 'changePassword']);
        });

        // Listagem de Perfis de Usuário
        Route::get('/roles', [RoleController::class, 'index']);

        // Gestão de Usuários (RBAC controlado via UserPolicy)
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

        // Gestão de Projetos e Membros da Equipe (RBAC via ProjectPolicy)
        Route::apiResource('projects', ProjectController::class);
        Route::prefix('projects/{project}')->group(function () {
            Route::get('/members', [ProjectMemberController::class, 'index']);
            Route::post('/members', [ProjectMemberController::class, 'store']);
            Route::put('/members/{user}', [ProjectMemberController::class, 'update']);
            Route::delete('/members/{user}', [ProjectMemberController::class, 'destroy']);
        });
    });
});
