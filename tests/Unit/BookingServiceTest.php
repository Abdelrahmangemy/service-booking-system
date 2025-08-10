<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_create_booking_for_available_slot()
    {
        $service = Service::factory()->create();
        $customer = User::factory()->customer()->create();

        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => Carbon::now()->addDay()->setTime(10, 0)->toISOString(),
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.service_id', $service->id);
    }

    /** @test */
    public function booking_fails_for_nonexistent_service()
    {
        $customer = User::factory()->customer()->create();
        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => 9999,
            'start_time' => Carbon::now()->addDay()->toISOString(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['service_id']);
    }

    /** @test */
    public function cannot_book_in_the_past()
    {
        $service = Service::factory()->create();
        $customer = User::factory()->customer()->create();
        Passport::actingAs($customer);

        $response = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => Carbon::now()->subDay()->toISOString(),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['start_time']);
    }
}
