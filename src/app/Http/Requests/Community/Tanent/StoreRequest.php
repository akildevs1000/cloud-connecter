<?php

namespace App\Http\Requests\Community\Tanent;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $room_id = $this->room_id;
        $companyId = $this->company_id;

        return [

            'room_id' => [
                'required',
                Rule::unique('tanents')->where(function ($query) use ($room_id, $companyId) {
                    return $query->where('room_id', $room_id)
                        ->where('company_id', $companyId);
                }),
            ],


            "full_name" => "nullable|min:3|max:20",
            "first_name" => "required|min:3|max:20",
            "last_name" => "required|min:3|max:20",
            "phone_number" => "required|min:10|max:20",
            "floor_id" => "required",
            "start_date" => "required",
            "end_date" => "required",
            "profile_picture" => "nullable",
            "system_user_id" => "nullable",
            "email" => "nullable",
            "company_id" => "required",

            "whatsapp_number" => "nullable",
            "date_of_birth" => "nullable",
            "car_number" => "nullable",
            "parking_number" => "nullable",


            "rfid" => "nullable",
            "pin" => "nullable",

            "nationality" => "required",
            "address" => "nullable",

            "passport_doc" => "nullable",
            "id_doc" => "nullable",
            "contract_doc" => "nullable",
            "ejari_doc" => "nullable",
            "license_doc" => "nullable",

            "web_access" => "nullable",

            "others_doc" => "nullable",

            "gender" => "required",
        ];
    }
}
