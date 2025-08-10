<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreAvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Models\Availability;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    private $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function index()
    {
        $provider = auth()->user();
        $avail = $provider->availabilities()->get();
        return AvailabilityResource::collection($avail);
    }

    public function store(StoreAvailabilityRequest $request)
    {
        $data = $request->validated();
        $data['provider_id'] = auth()->id();
        $availability = Availability::create($data);

        // Clear availability cache when new availability is added
        $this->availabilityService->clearCache(auth()->user());

        return new AvailabilityResource($availability->load('provider'));
    }

    public function destroy(Availability $availability)
    {
        if(auth()->id() !== $availability->provider_id) {
            return response()->json(['message'=>'Forbidden'], 403);
        }
        $availability->delete();

        // Clear availability cache when availability is deleted
        $this->availabilityService->clearCache(auth()->user());

        return response()->json(null,204);
    }
}
