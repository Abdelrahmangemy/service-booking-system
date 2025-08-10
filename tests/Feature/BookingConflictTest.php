<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Service;
use App\Models\Booking;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class BookingConflictTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cannot_book_same_slot_twice_for_same_service()
    {
        $provider = User::factory()->provider()->create();
        $service = Service::factory()->for($provider)->create();
        $customer = User::factory()->customer()->create();

        $slotTime = Carbon::now()->addDays(2)->setTime(10, 0)->toISOString();

        Booking::factory()->for($service)->for($customer, 'customer')->create([
            'start_time' => $slotTime
        ]);

        Passport::actingAs($customer);
        $response = $this->postJson('/api/bookings', [
            'service_id' => $service->id,
            'start_time' => $slotTime
        ]);

        $response->assertStatus(409)
                 ->assertJson(['error' => 'Slot already booked']);
    }
}
