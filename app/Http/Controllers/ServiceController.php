<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        // public listing only published
        $services = Service::where('is_published', true)
            ->with('provider')
            ->paginate(20);
        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request)
    {
        $data = $request->validated();
        $data['provider_id'] = auth()->id();
        $service = Service::create($data);
        return new ServiceResource($service->load('provider'));
    }

    public function show(Service $service)
    {
        if(!$service->is_published && (!auth()->check() || auth()->id() !== $service->provider_id)){
            return response()->json(['message'=>'Not found'], 404);
        }
        return new ServiceResource($service->load('provider'));
    }

    public function update(StoreServiceRequest $request, Service $service)
    {
        if(auth()->id() !== $service->provider_id){
            return response()->json(['message'=>'Forbidden'], 403);
        }
        $service->update($request->validated());
        return new ServiceResource($service->load('provider'));
    }

    public function destroy(Service $service)
    {
        if(auth()->id() !== $service->provider_id){
            return response()->json(['message'=>'Forbidden'], 403);
        }
        $service->delete();
        return response()->json(null, 204);
    }
}
