<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Booking;
use Laravel\Passport\Passport;

class ProviderConfirmBookingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function provider_can_confirm_booking()
    {
        $provider = User::factory()->provider()->create();
        $booking = Booking::factory()->for($provider, 'provider')->create();

        Passport::actingAs($provider);

        $response = $this->patchJson("/api/bookings/{$booking->id}/confirm");

        $response->assertStatus(200)
                 ->assertJsonPath('data.status', 'confirmed');
    }

    /** @test */
    public function provider_cannot_confirm_others_bookings()
    {
        $provider = User::factory()->provider()->create();
        $otherProvider = User::factory()->provider()->create();
        $booking = Booking::factory()->for($otherProvider, 'provider')->create();

        Passport::actingAs($provider);

        $response = $this->patchJson("/api/bookings/{$booking->id}/confirm");

        $response->assertStatus(403);
    }
}
