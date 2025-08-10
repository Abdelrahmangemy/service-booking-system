<?php
namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function totalBookingsPerProvider($filters = [])
    {
        $query = Booking::query()->select('provider_id', DB::raw('count(*) as total'));
        $this->applyFilters($query, $filters);
        return $query->groupBy('provider_id')->get();
    }

    public function canceledVsConfirmedRatePerService($filters = [])
    {
        $query = Booking::query()
            ->select('service_id',
                DB::raw("SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw('COUNT(*) as total')
            );
        $this->applyFilters($query, $filters);
        return $query->groupBy('service_id')->get();
    }

    public function peakHours($filters = [])
    {
        $query = Booking::query()
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as total'));
        $this->applyFilters($query, $filters);
        return $query->groupBy(DB::raw('HOUR(start_time)'))->orderBy('total', 'desc')->get();
    }

    public function avgBookingDurationPerCustomer($filters = [])
    {
        $query = Booking::query()
            ->select('customer_id', DB::raw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_minutes'));
        $this->applyFilters($query, $filters);
        return $query->groupBy('customer_id')->get();
    }

    protected function applyFilters(&$query, $filters)
    {
        if(!empty($filters['provider_id'])) $query->where('provider_id',$filters['provider_id']);
        if(!empty($filters['service_id'])) $query->where('service_id',$filters['service_id']);
        if(!empty($filters['from'])) $query->whereDate('start_time', '>=', Carbon::parse($filters['from']));
        if(!empty($filters['to'])) $query->whereDate('start_time', '<=', Carbon::parse($filters['to']));
    }
}
