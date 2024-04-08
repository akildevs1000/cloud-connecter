<?php

namespace App\Http\Requests\Leavecount;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    use failedValidationWithName;
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
    {$leave_type_id = $this->leave_type_id;
        $group_id = $this->group_id;
        $company_id = $this->company_id;
        return [

            'leave_type_id' => 'required',
            // Rule::unique('leave_count')->where(function ($query) use ($leave_type_id, $group_id, $company_id) {
            //     return $query->where('leave_type_id', $leave_type_id)
            //         ->where('group_id', $group_id)
            //         ->where('company_id', $company_id);
            // }),

            'company_id' => 'required',
            'group_id' => 'required',
            'leave_type_count' => 'required',

        ];
    }
}
