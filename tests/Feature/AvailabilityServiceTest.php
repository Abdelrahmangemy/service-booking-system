<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = new AvailabilityService();
    }

    /** @test */
    public function it_generates_slots_for_recurring_availability()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);

        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'recurring',
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        $this->assertNotEmpty($slots);
        $this->assertGreaterThan(0, count($slots));
    }

    /** @test */
    public function it_excludes_booked_slots_from_availability()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);
        $customer = User::factory()->customer()->create();

        // Create availability
        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'recurring',
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC'
        ]);

        // Create a booking
        $bookingTime = Carbon::now()->addDay()->setTime(10, 0);
        Booking::create([
            'service_id' => \App\Models\Service::factory()->create(['provider_id' => $provider->id])->id,
            'provider_id' => $provider->id,
            'customer_id' => $customer->id,
            'start_time' => $bookingTime,
            'end_time' => $bookingTime->copy()->addMinutes(60),
            'status' => 'confirmed'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        // Should not contain the booked slot
        foreach ($slots as $slot) {
            $this->assertFalse(
                $slot['start']->between($bookingTime, $bookingTime->copy()->addMinutes(60)) ||
                $slot['end']->between($bookingTime, $bookingTime->copy()->addMinutes(60))
            );
        }
    }

    /** @test */
    public function it_handles_timezone_conversions_correctly()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'America/New_York']);

        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'recurring',
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'America/New_York'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        $this->assertNotEmpty($slots);
        // All slots should be in UTC
        foreach ($slots as $slot) {
            $this->assertEquals('UTC', $slot['start']->timezone->getName());
            $this->assertEquals('UTC', $slot['end']->timezone->getName());
        }
    }

    /** @test */
    public function it_handles_custom_availability_dates()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);
        $customDate = Carbon::now()->addDay();

        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'custom',
            'date' => $customDate->toDateString(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'timezone' => 'UTC'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        $this->assertNotEmpty($slots);
        // Should have slots for the custom date
        $hasCustomDateSlots = false;
        foreach ($slots as $slot) {
            if ($slot['start']->isSameDay($customDate)) {
                $hasCustomDateSlots = true;
                break;
            }
        }
        $this->assertTrue($hasCustomDateSlots);
    }

    /** @test */
    public function it_excludes_past_slots()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);

        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'recurring',
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        foreach ($slots as $slot) {
            $this->assertTrue($slot['end']->isFuture(), 'Slot should not be in the past');
        }
    }

    /** @test */
    public function it_handles_empty_availability()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        $this->assertEmpty($slots);
    }

    /** @test */
    public function it_handles_overlapping_bookings()
    {
        $provider = User::factory()->provider()->create(['timezone' => 'UTC']);
        $customer = User::factory()->customer()->create();

        // Create availability
        Availability::create([
            'provider_id' => $provider->id,
            'type' => 'recurring',
            'day_of_week' => Carbon::now()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'UTC'
        ]);

        // Create overlapping bookings
        $bookingTime1 = Carbon::now()->addDay()->setTime(10, 0);
        $bookingTime2 = Carbon::now()->addDay()->setTime(10, 30);

        Booking::create([
            'service_id' => \App\Models\Service::factory()->create(['provider_id' => $provider->id])->id,
            'provider_id' => $provider->id,
            'customer_id' => $customer->id,
            'start_time' => $bookingTime1,
            'end_time' => $bookingTime1->copy()->addMinutes(60),
            'status' => 'confirmed'
        ]);

        Booking::create([
            'service_id' => \App\Models\Service::factory()->create(['provider_id' => $provider->id])->id,
            'provider_id' => $provider->id,
            'customer_id' => $customer->id,
            'start_time' => $bookingTime2,
            'end_time' => $bookingTime2->copy()->addMinutes(60),
            'status' => 'pending'
        ]);

        $slots = $this->availabilityService->getNextWeekSlots($provider);

        // Should not contain overlapping slots
        foreach ($slots as $slot) {
            $this->assertFalse(
                $slot['start']->between($bookingTime1, $bookingTime1->copy()->addMinutes(60)) ||
                $slot['end']->between($bookingTime1, $bookingTime1->copy()->addMinutes(60)) ||
                $slot['start']->between($bookingTime2, $bookingTime2->copy()->addMinutes(60)) ||
                $slot['end']->between($bookingTime2, $bookingTime2->copy()->addMinutes(60))
            );
        }
    }
}
