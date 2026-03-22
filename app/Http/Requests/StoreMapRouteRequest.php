<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMapRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'route_data' => ['required', 'array'],
            'route_data.type' => ['required', 'in:FeatureCollection'],
            'route_data.features' => ['required', 'array', 'min:1'],
            'route_data.features.*.type' => ['required', 'in:Feature'],
            'route_data.features.*.geometry.type' => ['required', 'in:LineString'],
            'route_data.features.*.geometry.coordinates' => ['required', 'array', 'min:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name before saving.',
            'route_data.features.min' => 'Draw at least one route segment before saving.',
        ];
    }
}
