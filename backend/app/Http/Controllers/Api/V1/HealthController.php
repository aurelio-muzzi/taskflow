<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Verifica a integridade dos serviços do sistema.
     */
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'connected';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'disconnected';
        }

        return ApiResponse::success([
            'status' => 'healthy',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database' => $dbStatus,
            'timestamp' => now()->toIso8601String(),
        ], 'System status retrieved successfully.');
    }
}
