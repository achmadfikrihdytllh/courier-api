<?php

namespace App\Http\Requests;

use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai untuk store (POST), update penuh (PUT) dan update parsial (PATCH).
 * Pada PATCH, field hanya divalidasi bila dikirim.
 */
class CourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];
        $courier = $this->route('courier'); // null saat store

        return [
            'name' => [...$required, 'string', 'min:2', 'max:100'],
            // Format nomor HP Indonesia: 08xx..., 628xx... atau +628xx...
            'phone' => [
                ...$required,
                'string',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/',
                Rule::unique('couriers', 'phone')->ignore($courier),
            ],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('couriers', 'email')->ignore($courier),
            ],
            // NIK: 16 digit
            'id_card_number' => [
                'sometimes', 'nullable', 'digits:16',
                Rule::unique('couriers', 'id_card_number')->ignore($courier),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'vehicle_type' => [...$required, Rule::enum(VehicleType::class)],
            // Wajib untuk semua kendaraan bermotor, kecuali sepeda
            'vehicle_plate' => [
                'sometimes', 'nullable', 'string', 'max:15',
                'required_unless:vehicle_type,'.VehicleType::Bicycle->value,
            ],
            'level' => [...$required, 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
