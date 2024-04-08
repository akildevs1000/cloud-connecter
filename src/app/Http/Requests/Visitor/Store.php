<?php

namespace App\Http\Requests\Visitor;

use Illuminate\Foundation\Http\FormRequest;

class Store extends FormRequest
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
        $validations = [];

        if ($this->logo) {
            $validations['logo'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validations['timezone_id'] = 'required';

        $validations["zone_id"] = "required";

        $validations['system_user_id'] = 'required|numeric|unique:visitors,system_user_id';

        $validations['visit_from'] = 'required|date';
        $validations['visit_to'] = 'required|date';
        $validations['purpose_id'] = 'required';


        $validations['first_name'] = 'required|string|max:255';
        $validations['last_name'] = 'required|string|max:255';
        $validations['gender'] = 'required|in:Male,Female';

        $validations['phone_number'] = 'required|string|max:255';
        $validations['email'] = 'nullable|email|max:255';
        $validations['visitor_company_name'] = 'required|string|max:255';

        $validations['id_type'] = 'required';
        $validations['id_number'] = 'required|string|max:255';
        $validations['id_copy'] = 'required';

        $validations['host_company_id'] = 'required';

        $validations['status_id'] = 'required';
        $validations['date'] = 'required|date';
        $validations["updated_by"] = "required";
        $validations['reason'] = 'required|string|max:255';
        $validations["company_id"] = "required";


        $validations["time_in"] = "required";
        $validations["time_out"] = "required";

        return $validations;
    }
}
