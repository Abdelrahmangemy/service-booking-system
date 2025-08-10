<?php
namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;

class AvailabilityService
{
    /**
     * Generate bookable time slots for the next week for a provider,
     * taking into account recurring availabilities and custom overrides,
     * convert times to UTC for comparisons.
     *
     * @param \App\Models\User $provider
     * @return array of slots each: ['start'=>CarbonUTC, 'end'=>CarbonUTC]
     */
    public function getNextWeekSlots($provider)
    {
        $cacheKey = "availability_slots_provider_{$provider->id}_" . Carbon::now()->startOfDay()->timestamp;

        return Cache::remember($cacheKey, 300, function () use ($provider) {
            $slots = [];
            $now = Carbon::now();
            $startDate = $now->copy()->startOfDay();
            $endDate = $startDate->copy()->addDays(7);

            $availabilities = $provider->availabilities()->get();

            // iterate each day in range
            for($date = $startDate->copy(); $date->lessThan($endDate); $date->addDay()){

                $daySlots = $this->generateSlotsForDay($date, $availabilities, $provider->timezone);
                $slots = array_merge($slots, $daySlots);
            }

            // Remove slots that are already booked
            $bookedSlots = $this->getBookedSlots($provider, $startDate, $endDate);
            $slots = $this->removeBookedSlots($slots, $bookedSlots);

            return $slots;
        });
    }

    /**
     * Generate slots for a specific day
     */
    private function generateSlotsForDay($date, $availabilities, $providerTimezone)
    {
        $slots = [];
        $dayOfWeek = $date->dayOfWeek;

        foreach($availabilities as $availability) {
            if($availability->type === 'recurring' && $availability->day_of_week == $dayOfWeek) {
                $slots = array_merge($slots, $this->generateSlotsFromAvailability($availability, $date, $providerTimezone));
            } elseif($availability->type === 'custom' && $availability->date && $availability->date->isSameDay($date)) {
                $slots = array_merge($slots, $this->generateSlotsFromAvailability($availability, $date, $providerTimezone));
            }
        }

        return $slots;
    }

    /**
     * Generate slots from a specific availability
     */
    private function generateSlotsFromAvailability($availability, $date, $providerTimezone)
    {
        $slots = [];
        $startTime = Carbon::parse($availability->start_time);
        $endTime = Carbon::parse($availability->end_time);

        // Convert to provider's timezone
        $startDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute);
        $endDateTime = $date->copy()->setTime($endTime->hour, $endTime->minute);

        // Convert to UTC for storage
        $startUTC = $startDateTime->setTimezone($providerTimezone)->setTimezone('UTC');
        $endUTC = $endDateTime->setTimezone($providerTimezone)->setTimezone('UTC');

        // Generate 30-minute slots
        $currentSlot = $startUTC->copy();
        while($currentSlot->lessThan($endUTC)) {
            $slotEnd = $currentSlot->copy()->addMinutes(30);
            if($slotEnd->lessThanOrEqualTo($endUTC)) {
                $slots[] = [
                    'start' => $currentSlot->copy(),
                    'end' => $slotEnd
                ];
            }
            $currentSlot->addMinutes(30);
        }

        return $slots;
    }

    /**
     * Get booked slots for a provider in a date range
     */
    private function getBookedSlots($provider, $startDate, $endDate)
    {
        return Booking::where('provider_id', $provider->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get(['start_time', 'end_time']);
    }

    /**
     * Remove booked slots from available slots
     */
    private function removeBookedSlots($availableSlots, $bookedSlots)
    {
        $filteredSlots = [];

        foreach($availableSlots as $slot) {
            $isBooked = false;

            foreach($bookedSlots as $bookedSlot) {
                if($this->slotsOverlap($slot, $bookedSlot)) {
                    $isBooked = true;
                    break;
                }
            }

            if(!$isBooked) {
                $filteredSlots[] = $slot;
            }
        }

        return $filteredSlots;
    }

    /**
     * Check if two slots overlap
     */
    private function slotsOverlap($slot1, $slot2)
    {
        $slot1Start = is_array($slot1) ? $slot1['start'] : $slot1->start_time;
        $slot1End = is_array($slot1) ? $slot1['end'] : $slot1->end_time;
        $slot2Start = is_array($slot2) ? $slot2['start'] : $slot2->start_time;
        $slot2End = is_array($slot2) ? $slot2['end'] : $slot2->end_time;

        return $slot1Start < $slot2End && $slot1End > $slot2Start;
    }

    /**
     * Clear availability cache for a provider
     */
    public function clearCache($provider)
    {
        $cacheKey = "availability_slots_provider_{$provider->id}_" . Carbon::now()->startOfDay()->timestamp;
        Cache::forget($cacheKey);
    }
}
