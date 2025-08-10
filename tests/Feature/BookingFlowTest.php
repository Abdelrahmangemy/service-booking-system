<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function full_booking_flow_from_customer_to_provider_confirmation()
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->for($provider)->create();
        $customer = User::factory()->customer()->create();

        Passport::actingAs($customer);

        $startTime = Carbon::now()->addDays(2)->setTime(10, 0)->toISOString();
        $bookingResponse = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => $startTime,
        ])->assertStatus(201);

        $bookingId = $bookingResponse->json('data.id');

        Passport::actingAs($provider);
        $confirmResponse = $this->patchJson("/api/bookings/{$bookingId}/confirm");

        $confirmResponse->assertStatus(200)
                        ->assertJsonPath('data.status', 'confirmed');
    }
}
