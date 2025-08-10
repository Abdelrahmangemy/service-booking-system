<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BookingsExport;

class ReportController extends Controller
{
    private $reportService;
    public function __construct(ReportService $r)
    {
        $this->reportService = $r;
        $this->middleware('auth:api');
    }

    protected function authorizeAdmin()
    {
        if(!auth()->user()->isAdmin()){
            abort(403,'Forbidden');
        }
    }

    public function bookings(Request $request)
    {
        $this->authorizeAdmin();
        $filters = $request->only(['provider_id','service_id','from','to']);
        $data = $this->reportService->totalBookingsPerProvider($filters);
        return response()->json($data);
    }

    public function exportBookings(Request $request)
    {
        $this->authorizeAdmin();
        $filters = $request->only(['provider_id','service_id','from','to','status']);
        $export = new \App\Exports\BookingsExport($filters);
        return Excel::download($export, 'bookings-report.csv');
    }
}

