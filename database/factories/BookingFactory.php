<?php
namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition()
    {
        $start = Carbon::now()->addDays(rand(1,7))->setTime(rand(8,17), 0);
        $duration = rand(30,120);
        return [
            'service_id' => Service::factory(),
            'provider_id' => function(array $attrs){
                // get provider from created service if exists
                return Service::find($attrs['service_id'])->provider_id ?? User::factory();
            },
            'customer_id' => User::factory(),
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes($duration),
            'status' => 'confirmed'
        ];
    }
}
