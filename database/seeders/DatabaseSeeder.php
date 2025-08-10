<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RolesTableSeeder::class);

        $adminRole = Role::where('name','admin')->first();
        $providerRole = Role::where('name','provider')->first();
        $customerRole = Role::where('name','customer')->first();

        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'timezone' => 'UTC'
        ]);

        // Providers & their services & availability
        for($i=0;$i<3;$i++){
            $provider = User::factory()->create(['role_id' => $providerRole->id, 'timezone' => 'UTC']);
            Service::factory()->count(3)->create([
                'provider_id' => $provider->id,
                'is_published' => true
            ]);
            Availability::factory()->count(7)->create([
                'provider_id' => $provider->id,
                'type' => 'recurring'
            ]);
        }

        // Customers
        for($i=0;$i<10;$i++){
            User::factory()->create(['role_id' => $customerRole->id]);
        }
    }
}
