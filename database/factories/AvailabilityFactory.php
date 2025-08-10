<?php
namespace Database\Factories;

use App\Models\Availability;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityFactory extends Factory
{
    protected $model = Availability::class;

    public function definition()
    {
        $start = $this->faker->time('H:i:s', '09:00');
        $end   = $this->faker->time('H:i:s', '17:00');
        return [
            'type' => 'recurring',
            'day_of_week' => $this->faker->numberBetween(0,6),
            'start_time' => $start,
            'end_time' => $end,
            'timezone' => 'UTC'
        ];
    }
}
