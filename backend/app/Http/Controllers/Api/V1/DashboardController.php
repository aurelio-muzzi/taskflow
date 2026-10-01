<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\DashboardMetricsService;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Retorna indicadores e métricas consolidadas do Dashboard.
     */
    public function metrics(Request $request, DashboardMetricsService $service): JsonResponse
    {
        $metrics = $service->getMetrics($request->user());

        return ApiResponse::success(
            data: $metrics,
            message: 'Métricas do Dashboard calculadas com sucesso.'
        );
    }

    /**
     * Realiza busca combinada em tempo real em projetos, tarefas e usuários.
     */
    public function search(Request $request, GlobalSearchService $service): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $limit = min((int) $request->input('limit', 5), 20);

        $results = $service->search($request->user(), $query, $limit);

        return ApiResponse::success(
            data: $results,
            message: 'Busca global realizada com sucesso.'
        );
    }
}
