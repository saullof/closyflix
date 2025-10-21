<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawalRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => 'required',
            'method' => 'required',
            'identifier' => '',
            'message' => '',
            'pix_key_type' => 'nullable|string',
            'pix_beneficiary_name' => 'nullable|string',
            'pix_document' => 'nullable|string',
        ];
    }
}
