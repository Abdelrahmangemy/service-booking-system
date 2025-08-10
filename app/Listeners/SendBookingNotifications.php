<?php
namespace App\Listeners;

use App\Events\BookingCreated;
use App\Events\BookingConfirmed;
use App\Notifications\BookingStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle($event)
    {
        $booking = $event->booking ?? null;
        if(!$booking) return;

        // Notify customer and provider
        $booking->customer->notify(new BookingStatusChanged($booking));
        $booking->provider->notify(new BookingStatusChanged($booking));
    }
}
