<?php

namespace Tests\Unit;

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use App\Enums\RoleEnum;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserStatus;
use App\Http\Responses\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnumAndResponseTest extends TestCase
{
    public function test_api_response_success_structure(): void
    {
        $response = ApiResponse::success(['key' => 'value'], 'Operação com sucesso', Response::HTTP_OK);
        $data = $response->getData(true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Operação com sucesso', $data['message']);
        $this->assertEquals(['key' => 'value'], $data['data']);
    }

    public function test_api_response_error_structure(): void
    {
        $response = ApiResponse::error('Falha na requisição', ['field' => ['inválido']], Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = $response->getData(true);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Falha na requisição', $data['message']);
        $this->assertEquals(['field' => ['inválido']], $data['errors']);
    }

    public function test_api_response_paginated_structure(): void
    {
        $paginator = new LengthAwarePaginator(
            items: ['item1', 'item2'],
            total: 10,
            perPage: 2,
            currentPage: 1
        );

        $response = ApiResponse::paginated(['item1', 'item2'], $paginator, 'Paginado com sucesso');
        $data = $response->getData(true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Paginado com sucesso', $data['message']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertEquals(5, $data['meta']['last_page']);
        $this->assertEquals(10, $data['meta']['total']);
        $this->assertEquals(2, $data['meta']['per_page']);
        $this->assertEquals(['item1', 'item2'], $data['data']['items']);
        $this->assertArrayHasKey('pagination', $data['data']);
    }

    public function test_task_status_enum_values_and_labels(): void
    {
        $values = TaskStatus::values();
        $this->assertContains('todo', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('review', $values);
        $this->assertContains('done', $values);

        $this->assertEquals('A Fazer', TaskStatus::TODO->label());
        $this->assertEquals('Em Progresso', TaskStatus::IN_PROGRESS->label());
        $this->assertEquals('Em Revisão', TaskStatus::REVIEW->label());
        $this->assertEquals('Concluída', TaskStatus::DONE->label());
    }

    public function test_task_priority_enum_values_and_labels(): void
    {
        $values = TaskPriority::values();
        $this->assertContains('low', $values);
        $this->assertContains('medium', $values);
        $this->assertContains('high', $values);
        $this->assertContains('urgent', $values);

        $this->assertEquals('Baixa', TaskPriority::LOW->label());
        $this->assertEquals('Média', TaskPriority::MEDIUM->label());
        $this->assertEquals('Alta', TaskPriority::HIGH->label());
        $this->assertEquals('Urgente', TaskPriority::URGENT->label());
    }

    public function test_project_status_enum_values_and_labels(): void
    {
        $values = ProjectStatus::values();
        $this->assertContains('PLANNING', $values);
        $this->assertContains('ACTIVE', $values);
        $this->assertContains('ON_HOLD', $values);
        $this->assertContains('COMPLETED', $values);
        $this->assertContains('ARCHIVED', $values);

        $this->assertEquals('Planejamento', ProjectStatus::PLANNING->label());
        $this->assertEquals('Ativo', ProjectStatus::ACTIVE->label());
        $this->assertEquals('Concluído', ProjectStatus::COMPLETED->label());
    }

    public function test_project_role_enum_values_and_labels(): void
    {
        $values = ProjectRole::values();
        $this->assertContains('OWNER', $values);
        $this->assertContains('MANAGER', $values);
        $this->assertContains('MEMBER', $values);
        $this->assertContains('VIEWER', $values);

        $this->assertEquals('Proprietário', ProjectRole::OWNER->label());
        $this->assertEquals('Gerente', ProjectRole::MANAGER->label());
        $this->assertEquals('Membro', ProjectRole::MEMBER->label());
        $this->assertEquals('Visualizador', ProjectRole::VIEWER->label());
    }

    public function test_user_status_and_role_enums(): void
    {
        $userStatusValues = UserStatus::values();
        $this->assertContains('ACTIVE', $userStatusValues);
        $this->assertContains('INACTIVE', $userStatusValues);

        $this->assertEquals('admin', RoleEnum::ADMIN->value);
        $this->assertEquals('manager', RoleEnum::MANAGER->value);
        $this->assertEquals('user', RoleEnum::USER->value);
    }
}
