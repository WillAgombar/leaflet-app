<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignAreaRoutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'points' => ['required', 'array', 'min:3', 'max:60'],
            'points.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'points.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a route name before generating.',
            'points.required' => 'Select an area before generating routes.',
            'points.min' => 'Select at least three points to define the area.',
        ];
    }
}
