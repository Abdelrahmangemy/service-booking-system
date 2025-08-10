<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Log;

class RequestResponseLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_api_requests_and_responses()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::type('array'))
            ->once();

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_logs_authenticated_requests_with_user_info()
    {
        $user = User::factory()->customer()->create();
        Passport::actingAs($user);

        Log::shouldReceive('channel')
            ->with('api')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::on(function ($data) use ($user) {
                return $data['user_id'] === $user->id &&
                       $data['user_role'] === 'customer';
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::type('array'))
            ->once();

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_sanitizes_sensitive_data_in_logs()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::on(function ($data) {
                return $data['body']['password'] === '***REDACTED***';
            }))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::type('array'))
            ->once();

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'secretpassword'
        ]);

        $response->assertStatus(401); // Should fail without proper credentials
    }

    /** @test */
    public function it_logs_execution_time()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::on(function ($data) {
                return isset($data['execution_time']) &&
                       strpos($data['execution_time'], 'ms') !== false;
            }))
            ->once();

        $response = $this->getJson('/api/services');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_logs_error_responses()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::on(function ($data) {
                return $data['status_code'] === 404;
            }))
            ->once();

        $response = $this->getJson('/api/services/99999');

        $response->assertStatus(404);
    }
}
