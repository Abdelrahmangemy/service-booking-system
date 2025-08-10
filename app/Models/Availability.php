<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'type',
        'day_of_week',
        'date',
        'start_time',
        'end_time',
        'timezone'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
