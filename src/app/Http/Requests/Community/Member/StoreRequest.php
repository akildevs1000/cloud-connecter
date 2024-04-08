<?php

namespace App\Http\Requests\Community\Member;

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
        return [
            'full_name' => 'required|string|max:255',
            'phone_number' => 'nullable',
            'age' => 'required|string|max:255',
            'system_user_id' => 'required|numeric',
            'tanent_id' => 'required|numeric',
            'company_id' => 'required|numeric',
            'profile_picture' => 'nullable',
            'member_type' => 'required',
            'nationality' => 'required',
        ];
    }
}
