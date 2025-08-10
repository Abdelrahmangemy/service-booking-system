<?php
namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use Carbon\Carbon;
use DateTimeZone;

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
        $slots = [];
        $now = Carbon::now();
        $startDate = $now->copy()->startOfDay();
        $endDate = $startDate->copy()->addDays(7);

        $availabilities = $provider->availabilities()->get();

        // iterate each day in range
        for($date = $startDate->copy(); $date->lessThan($endDate); $date->addDay()){
            foreach($availabilities as $avail){
                if($avail->type === 'recurring'){
                    if($avail->day_of_week == $date->dayOfWeek){
                        $slotStartLocal = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $avail->start_time, $avail->timezone);
                        $slotEndLocal   = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $avail->end_time, $avail->timezone);
                        $slots[] = [
                            'start' => $slotStartLocal->copy()->setTimezone('UTC'),
                            'end' => $slotEndLocal->copy()->setTimezone('UTC'),
                        ];
                    }
                } else {
                    // custom date
                    if($avail->date && $avail->date->isSameDay($date)){
                        $slotStartLocal = Carbon::createFromFormat('Y-m-d H:i', $avail->date->format('Y-m-d') . ' ' . $avail->start_time, $avail->timezone);
                        $slotEndLocal   = Carbon::createFromFormat('Y-m-d H:i', $avail->date->format('Y-m-d') . ' ' . $avail->end_time, $avail->timezone);
                        $slots[] = [
                            'start' => $slotStartLocal->copy()->setTimezone('UTC'),
                            'end' => $slotEndLocal->copy()->setTimezone('UTC'),
                        ];
                    }
                }
            }
        }

        // remove slots that are in the past
        $slots = array_filter($slots, function($s){
            return $s['end']->isFuture();
        });

        // optionally subtract existing confirmed bookings (see BookingService)
        return array_values($slots);
    }

    /**
     * Return availability slots in provider's timezone with readable format.
     */
    public function formatSlotsForProviderTimezone($slots, $timezone)
    {
        return array_map(function($slot) use ($timezone){
            return [
                'start_local' => $slot['start']->copy()->setTimezone($timezone)->toDateTimeString(),
                'end_local' => $slot['end']->copy()->setTimezone($timezone)->toDateTimeString(),
                'start_utc' => $slot['start']->toDateTimeString(),
                'end_utc' => $slot['end']->toDateTimeString()
            ];
        }, $slots);
    }
}
