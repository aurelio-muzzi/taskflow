<?php

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // API Stateless com Bearer Tokens (Laravel Sanctum)
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Envelopamento padronizado de erros de validação
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Erro de validação nos dados fornecidos.',
                    $e->errors(),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        });

        // Envelopamento padronizado de erro de autenticação (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Acesso não autenticado. Por favor, faça login para continuar.',
                    null,
                    Response::HTTP_UNAUTHORIZED
                );
            }
        });

        // Envelopamento padronizado de erro de autorização / RBAC (403)
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'Você não possui permissão para executar esta ação.',
                    null,
                    Response::HTTP_FORBIDDEN
                );
            }
        });

        // Envelopamento padronizado de recursos não encontrados (404)
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return ApiResponse::error(
                    'O recurso solicitado não foi encontrado.',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }
        });
    })->create();
