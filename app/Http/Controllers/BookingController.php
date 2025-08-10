<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private $bookingService;

    public function __construct(BookingService $bs)
    {
        $this->bookingService = $bs;
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if($user->isProvider()){
            $bookings = Booking::where('provider_id',$user->id)->with('service','customer')->paginate(20);
        } elseif ($user->isCustomer()){
            $bookings = Booking::where('customer_id',$user->id)->with('service','provider')->paginate(20);
        } else {
            $bookings = Booking::with('service','provider','customer')->paginate(20);
        }
        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $data['customer_id'] = auth()->id();
        $booking = $this->bookingService->createBooking($data);
        return new BookingResource($booking->load('service', 'provider', 'customer'));
    }

    public function confirm(Booking $booking)
    {
        $user = auth()->user();
        $this->bookingService->confirmBooking($booking, $user);
        return new BookingResource($booking->fresh()->load('service', 'provider', 'customer'));
    }

    public function cancel(Booking $booking)
    {
        $user = auth()->user();
        $this->bookingService->cancelBooking($booking, $user);
        return new BookingResource($booking->fresh()->load('service', 'provider', 'customer'));
    }
}
