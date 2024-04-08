<?php

namespace App\Http\Requests;

use App\Rules\UniqueValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', new UniqueValidation('zones', 'name')],
            'device_ids' => 'required|array',
            'device_ids.*' => 'required|numeric',
            "company_id" => "required",
        ];
    }
}
