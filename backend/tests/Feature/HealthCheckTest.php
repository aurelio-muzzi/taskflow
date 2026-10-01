<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test the API v1 health check endpoint.
     */
    public function test_api_v1_health_check_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'System status retrieved successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'app',
                    'environment',
                    'php_version',
                    'laravel_version',
                    'database',
                    'timestamp',
                ],
            ]);
    }
}
