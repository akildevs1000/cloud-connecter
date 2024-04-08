<?php

namespace App\Http\Requests\Community\Tanent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            "full_name" => "nullable|min:3|max:20",
            "first_name" => "required|min:3|max:20",
            "last_name" => "required|min:3|max:20",
            "phone_number" => "required|min:10|max:20",
            "floor_id" => "required",
            "room_id" => "required",
            "start_date" => "required",
            "end_date" => "required",
            "profile_picture" => "nullable",
            "attachment" => "nullable",
            "system_user_id" => "nullable",
            "email" => "nullable",
            "company_id" => "nullable",

            "whatsapp_number" => "nullable",
            "date_of_birth" => "required",
            "nationality" => "required",
            "car_number" => "nullable",
            "parking_number" => "nullable",
            "web_access" => "required",
            "rfid" => "required",
            "pin" => "required",
            "address" => "required",
            
            "passport_doc" => "nullable",
            "id_doc" => "nullable",
            "contract_doc" => "nullable",
            "ejari_doc" => "nullable",
            "license_doc" => "nullable",
            "others_doc" => "nullable",

            "gender" => "required",
        ];
    }
}
