<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // only providers can create service
        return auth()->check() && auth()->user()->isProvider();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'duration_minutes' => 'required|integer|min:15',
            'price_cents' => 'required|integer|min:0',
            'is_published' => 'boolean'
        ];
    }
}
