<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Service;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'start_time' => 'required|date|after:now',
            'timezone' => 'nullable|string' // customer timezone optional
        ];
    }
}

