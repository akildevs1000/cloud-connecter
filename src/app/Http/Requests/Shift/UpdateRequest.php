<?php

namespace App\Http\Requests\Shift;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
    {
        if ($this->shift_type_id == 3) {
            return [];
        }
        return [
            'name' => ['required', Rule::unique('shifts')->ignore($this->input('id'))->where(function ($query) {
                return $query->where('branch_id', $this->input('branch_id'));
            })],
            'overtime_interval' => ["required"],
            'shift_type_id' => ["required"],
            'company_id' => ["required"],
            'working_hours' => ['nullable'],
            'days' => ['nullable'],
            'break' => ["nullable"],
            'on_duty_time' => ["required"],
            'off_duty_time' => ["required"],
            'from_date' => ["required"],
            'to_date' => ["required"],
            'late_time' => 'nullable',
            'early_time' => 'nullable',
            'beginning_in' => 'nullable',
            'ending_in' => 'nullable',
            'beginning_out' => 'nullable',
            'ending_out' => 'nullable',
            'absent_min_in' => 'nullable',
            'absent_min_out' => 'nullable',
            'gap_in' => 'nullable',
            'gap_out' => 'nullable',

            // columns for split shift only
            'on_duty_time1' => 'nullable',
            'off_duty_time1' => 'nullable',
            'beginning_in1' => 'nullable',
            'ending_in1' => 'nullable',
            'beginning_out1' => 'nullable',
            'ending_out1' => 'nullable',

            'weekend1' => 'nullable',
            'weekend2' => 'nullable',
            'monthly_flexi_holidays' => 'nullable',

            'branch_id' => 'nullable',

            'halfday'               => 'nullable',
            'halfday_working_hours' => 'nullable',

            'isAutoShift' => "nullable",

        ];
    }
}
