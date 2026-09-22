<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLandingCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_flat_id' => ['required', 'integer', 'exists:product_flats,id'],
            'quantity' => ['required', 'integer', 'between:1,100'],
            'shipping_rate_quote_id' => ['required', 'uuid', 'exists:shipping_rate_quotes,public_id'],
            'payment_mode' => ['required', Rule::in(['online', 'cod'])],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'contact_email' => ['required', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['required', 'string', 'max:20'],
            'area_string' => ['required', 'string', 'max:255'],
            'destination_area_id' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'click_id' => ['nullable', 'string', 'max:255'],
            'marketing_event_id' => ['nullable', 'uuid'],
        ];
    }
}
