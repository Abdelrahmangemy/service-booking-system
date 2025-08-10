<?php
namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email'=> $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'timezone' => 'UTC'
        ];
    }

    public function withRole($roleId){
        return $this->state(fn(array $attributes) => ['role_id' => $roleId]);
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            return ['role_id' => \App\Models\Role::where('name', 'admin')->first()->id];
        });
    }

    public function provider()
    {
        return $this->state(function (array $attributes) {
            return ['role_id' => \App\Models\Role::where('name', 'provider')->first()->id];
        });
    }

    public function customer()
    {
        return $this->state(function (array $attributes) {
            return ['role_id' => \App\Models\Role::where('name', 'customer')->first()->id];
        });
    }
}
