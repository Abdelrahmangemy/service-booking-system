<?php
namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    private $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $b = $this->booking;
        return (new MailMessage)
            ->subject("Booking #{$b->id} - {$b->status}")
            ->greeting("Hello {$notifiable->name}")
            ->line("Booking for service: {$b->service->title}")
            ->line("Status: {$b->status}")
            ->line("Start: {$b->start_time->toDateTimeString()} UTC")
            ->line("End: {$b->end_time->toDateTimeString()} UTC")
            ->action('View Booking', url('/bookings/'.$b->id))
            ->line('Thank you for using our service!');
    }
}
