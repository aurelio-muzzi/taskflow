<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['slug' => RoleEnum::ADMIN->value],
            ['name' => 'Administrador', 'description' => 'Acesso total.']
        );

        $this->userRole = Role::firstOrCreate(
            ['slug' => RoleEnum::USER->value],
            ['name' => 'Usuário', 'description' => 'Acesso comum.']
        );
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
            'email' => 'dev@taskflow.com',
            'password' => Hash::make('Secret123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'dev@taskflow.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Autenticação realizada com sucesso.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role' => ['id', 'name', 'slug'],
                    ],
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'role_id' => $this->userRole->id,
            'email' => 'dev@taskflow.com',
            'password' => Hash::make('Secret123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'dev@taskflow.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'role_id' => $this->userRole->id,
            'email' => 'inactive@taskflow.com',
            'password' => Hash::make('Secret123!'),
            'status' => UserStatus::INACTIVE,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@taskflow.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_request_to_me_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
            'email' => 'active@taskflow.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => 'active@taskflow.com',
                ],
            ]);
    }

    public function test_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sessão encerrada com sucesso.',
            ]);

        // Verifica se o token foi revogado
        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
            'name' => 'Original Name',
            'email' => 'original@taskflow.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@taskflow.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@taskflow.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@taskflow.com',
        ]);
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/v1/auth/change-password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada com sucesso.',
            ]);

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
