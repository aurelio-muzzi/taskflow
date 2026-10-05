<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Retorna uma resposta JSON de sucesso padronizada.
     */
    public static function success(mixed $data = null, string $message = 'Operation completed successfully.', int $status = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Retorna uma resposta JSON de erro padronizada.
     */
    public static function error(string $message = 'An error occurred.', mixed $errors = null, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Retorna uma resposta JSON paginada padronizada conforme contrato.
     */
    public static function paginated(mixed $items, mixed $paginator, string $message = 'List retrieved successfully.'): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'items' => $items,
                'pagination' => $meta,
                'meta' => $meta,
            ],
            'meta' => $meta,
        ], Response::HTTP_OK);
    }
}
