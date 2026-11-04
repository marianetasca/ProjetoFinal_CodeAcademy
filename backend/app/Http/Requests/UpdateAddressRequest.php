<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'street'     => ['required', 'string', 'max:255'],
            'number'     => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'district'   => ['required', 'string', 'max:255'],
            'city'       => ['required', 'string', 'max:255'],
            'state'      => ['required', 'string', 'size:2'],                      // sigla: PR, SP...
            'zip_code'   => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],      // 85010-000 ou 85010000
        ];
    }
}
