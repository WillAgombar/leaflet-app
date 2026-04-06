<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteTrackingPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'snapped_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'snapped_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'captured_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required to save tracking points.',
            'longitude.required' => 'Longitude is required to save tracking points.',
        ];
    }
}
