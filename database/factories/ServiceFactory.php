<?php
namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        // make sure to provide provider_id when using factory unless you want random
        return [
            'title' => $this->faker->company . ' Service',
            'description' => $this->faker->sentence,
            'category' => $this->faker->word,
            'duration_minutes' => $this->faker->randomElement([30,60,90]),
            'price_cents' => $this->faker->numberBetween(1000, 10000),
            'is_published' => true,
        ];
    }
}
