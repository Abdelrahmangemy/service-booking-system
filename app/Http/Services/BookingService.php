<?php
namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\BookingCreated;
use App\Events\BookingConfirmed;

class BookingService
{
    /**
     * Create booking with conflict checks.
     *
     * @param array $data ['service_id','start_time','customer_id', ...]
     * @return Booking
     * @throws \Exception on conflict
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function() use ($data) {
            $service = Service::findOrFail($data['service_id']);
            $provider = $service->provider;
            $customerId = $data['customer_id'];

            $start = Carbon::parse($data['start_time'])->setTimezone('UTC');
            $end = $start->copy()->addMinutes($service->duration_minutes);

            // Prevent past booking
            if($start->isPast()){
                throw new \InvalidArgumentException('Cannot book in the past.');
            }

            // Overlap check: any confirmed or pending booking that overlaps?
            $conflict = Booking::where('provider_id', $provider->id)
                ->whereIn('status', ['pending','confirmed'])
                ->where(function($q) use ($start, $end){
                    $q->whereBetween('start_time', [$start, $end->subSecond()])
                      ->orWhereBetween('end_time', [$start->addSecond(), $end])
                      ->orWhereRaw('? BETWEEN start_time AND end_time', [$start->toDateTimeString()])
                      ->orWhereRaw('? BETWEEN start_time AND end_time', [$end->toDateTimeString()]);
                })->exists();

            if($conflict){
                throw new \RuntimeException('Time slot is not available.');
            }

            $booking = Booking::create([
                'service_id' => $service->id,
                'provider_id' => $provider->id,
                'customer_id' => $customerId,
                'start_time' => $start,
                'end_time' => $end,
                'status' => 'pending'
            ]);

            event(new BookingCreated($booking));
            return $booking;
        });
    }

    public function confirmBooking(Booking $booking, User $actor)
    {
        // only provider can confirm
        if($actor->id !== $booking->provider_id){
            throw new \RuntimeException('Only provider can confirm booking.');
        }
        $booking->status = 'confirmed';
        $booking->save();
        event(new BookingConfirmed($booking));
        return $booking;
    }

    public function cancelBooking(Booking $booking, User $actor)
    {
        if(!in_array($actor->id, [$booking->provider_id, $booking->customer_id]) && !$actor->isAdmin()){
            throw new \RuntimeException('Not authorized to cancel.');
        }
        $booking->status = 'cancelled';
        $booking->save();
        // event(new BookingCancelled(...)) optionally
        return $booking;
    }
}
