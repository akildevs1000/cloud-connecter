<?php

namespace App\Http\Requests\MailContent;

use App\Models\MailContent;
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
        $name = $this->name;
        $companyId = $this->company_id;
        return [
            'name' => ['required', Rule::unique('mail_contents')->ignore($this->input('id'))->where(function ($query) {
                return $query->where('branch_id', $this->input('branch_id'));
            })],
            'name' => 'required',
            'company_id' => 'required',
            'content' => 'required',
            'branch_id' => 'required',
        ];
    }
}
