<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Booking;
use Laravel\Passport\Passport;

class AdminReportExportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_export_bookings_as_csv()
    {
        $admin = User::factory()->admin()->create();
        Booking::factory()->count(3)->create();

        Passport::actingAs($admin);

        $response = $this->get('/api/admin/reports/bookings?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Booking ID', $response->getContent());
    }

    /** @test */
    public function non_admin_cannot_access_reports()
    {
        $provider = User::factory()->provider()->create();
        Passport::actingAs($provider);

        $response = $this->get('/api/admin/reports/bookings?export=csv');

        $response->assertStatus(403);
    }
}
