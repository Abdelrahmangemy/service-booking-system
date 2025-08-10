<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class PurgeOldBookings extends Command
{
    protected $signature = 'bookings:purge {--hours=24}';
    protected $description = 'Purge pending bookings older than X hours';

    public function handle()
    {
        $hours = $this->option('hours') ?? 24;
        $cutoff = Carbon::now()->subHours($hours);
        $count = Booking::where('status','pending')->where('created_at','<',$cutoff)->delete();
        $this->info("Purged {$count} pending bookings older than {$hours} hours");
    }
}
