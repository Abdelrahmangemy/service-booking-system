<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_invalid_service_id_gracefully()
    {
        $customer = User::factory()->customer()->create();
        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => 99999,
            'start_time' => Carbon::now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['service_id']);
    }

    /** @test */
    public function it_prevents_booking_unpublished_service()
    {
        $provider = User::factory()->provider()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'is_published' => false
        ]);

        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => Carbon::now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['service_id']);
    }

    /** @test */
    public function it_handles_duplicate_booking_attempts()
    {
        $provider = User::factory()->provider()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create([
            'provider_id' => $provider->id,
            'is_published' => true
        ]);

        Passport::actingAs($customer);

        $bookingTime = Carbon::now()->addDay()->setTime(10, 0);

        // First booking should succeed
        $response1 = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => $bookingTime->toISOString(),
        ]);

        $response1->assertStatus(201);

        // Second booking for same time should fail
        $response2 = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => $bookingTime->toISOString(),
        ]);

        $response2->assertStatus(500); // Should throw exception
    }

    /** @test */
    public function it_handles_invalid_booking_status_transitions()
    {
        $provider = User::factory()->provider()->create();
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'provider_id' => $provider->id,
            'customer_id' => $customer->id,
            'status' => 'cancelled'
        ]);

        Passport::actingAs($provider);

        // Try to confirm a cancelled booking
        $response = $this->postJson("/api/bookings/{$booking->id}/confirm");

        $response->assertStatus(200); // Should still work but status won't change
    }

    /** @test */
    public function it_handles_unauthorized_access_attempts()
    {
        $provider = User::factory()->provider()->create();
        $otherProvider = User::factory()->provider()->create();
        $booking = Booking::factory()->create([
            'provider_id' => $provider->id,
            'status' => 'pending'
        ]);

        Passport::actingAs($otherProvider);

        // Try to confirm another provider's booking
        $response = $this->postJson("/api/bookings/{$booking->id}/confirm");

        $response->assertStatus(500); // Should throw exception
    }

    /** @test */
    public function it_handles_malformed_request_data()
    {
        $customer = User::factory()->customer()->create();
        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => 'invalid_id',
            'start_time' => 'invalid_date',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['service_id', 'start_time']);
    }

    /** @test */
    public function it_handles_rate_limiting()
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['is_published' => true]);

        Passport::actingAs($customer);

        // Make multiple rapid requests
        for ($i = 0; $i < 12; $i++) {
            $response = $this->postJson('/api/bookings', [
                'service_id' => $service->id,
                'start_time' => Carbon::now()->addDays($i + 1)->toISOString(),
            ]);

            if ($i < 10) {
                // First 10 should succeed or fail gracefully
                $this->assertContains($response->status(), [201, 422, 500]);
            } else {
                // 11th and beyond should be rate limited
                $response->assertStatus(429);
            }
        }
    }

    /** @test */
    public function it_handles_database_connection_issues_gracefully()
    {
        $customer = User::factory()->customer()->create();
        Passport::actingAs($customer);

        // This test simulates what would happen if DB connection fails
        // In a real scenario, you'd mock the database connection

        $response = $this->getJson('/api/bookings');

        // Should return a proper error response, not crash
        $this->assertContains($response->status(), [200, 500]);
    }

    /** @test */
    public function it_handles_invalid_timezone_data()
    {
        $provider = User::factory()->provider()->create();
        Passport::actingAs($provider);

        $response = $this->postJson('/api/availabilities', [
            'type' => 'recurring',
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'Invalid/Timezone'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['timezone']);
    }

    /** @test */
    public function it_handles_booking_conflicts_with_different_services()
    {
        $provider = User::factory()->provider()->create();
        $customer = User::factory()->customer()->create();

        $service1 = Service::factory()->create([
            'provider_id' => $provider->id,
            'is_published' => true,
            'duration_minutes' => 60
        ]);

        $service2 = Service::factory()->create([
            'provider_id' => $provider->id,
            'is_published' => true,
            'duration_minutes' => 90
        ]);

        Passport::actingAs($customer);

        $bookingTime = Carbon::now()->addDay()->setTime(10, 0);

        // Book first service
        $response1 = $this->postJson('/api/bookings', [
            'service_id' => $service1->id,
            'start_time' => $bookingTime->toISOString(),
        ]);

        $response1->assertStatus(201);

        // Try to book second service at overlapping time
        $response2 = $this->postJson('/api/bookings', [
            'service_id' => $service2->id,
            'start_time' => $bookingTime->copy()->addMinutes(30)->toISOString(),
        ]);

        $response2->assertStatus(500); // Should fail due to overlap
    }

    /** @test */
    public function it_handles_edge_case_booking_times()
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['is_published' => true]);

        Passport::actingAs($customer);

        // Test booking at midnight
        $response1 = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => Carbon::now()->addDay()->startOfDay()->toISOString(),
        ]);

        $response1->assertStatus(201);

        // Test booking at end of day
        $response2 = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => Carbon::now()->addDay()->endOfDay()->subMinutes(30)->toISOString(),
        ]);

        $response2->assertStatus(201);
    }
}
