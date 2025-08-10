<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Service;

class ServicePolicy
{
    public function update(User $user, Service $service)
    {
        return $user->id === $service->provider_id;
    }

    public function delete(User $user, Service $service)
    {
        return $user->id === $service->provider_id;
    }
}
