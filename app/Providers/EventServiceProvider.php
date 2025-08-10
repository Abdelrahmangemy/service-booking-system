<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\BookingCreated;
use App\Events\BookingConfirmed;
use App\Listeners\SendBookingNotifications;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingCreated::class => [
            SendBookingNotifications::class,
        ],
        BookingConfirmed::class => [
            SendBookingNotifications::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}
