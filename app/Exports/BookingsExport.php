<?php
namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BookingsExport implements FromQuery, WithHeadings
{
    protected $filters;
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $q = Booking::query()->with('service','provider','customer');
        if(!empty($this->filters['from'])) $q->whereDate('start_time','>=',$this->filters['from']);
        if(!empty($this->filters['to'])) $q->whereDate('start_time','<=',$this->filters['to']);
        if(!empty($this->filters['provider_id'])) $q->where('provider_id',$this->filters['provider_id']);
        if(!empty($this->filters['service_id'])) $q->where('service_id',$this->filters['service_id']);
        if(!empty($this->filters['status'])) $q->where('status',$this->filters['status']);
        return $q;
    }

    public function headings(): array
    {
        return ['ID','Service','Provider','Customer','Start','End','Status'];
    }
}
