<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'timezone'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'provider_id');
    }

    public function availabilities()
    {
        return $this->hasMany(Availability::class, 'provider_id');
    }

    public function bookingsAsProvider()
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function bookingsAsCustomer()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function isAdmin()
    {
        return $this->role && $this->role->name === 'admin';
    }

    public function isProvider()
    {
        return $this->role && $this->role->name === 'provider';
    }

    public function isCustomer()
    {
        return $this->role && $this->role->name === 'customer';
    }
}
