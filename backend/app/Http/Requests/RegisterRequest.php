<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    // true = qualquer pessoa pode enviar esta requisição (o registro é público)
    public function authorize(): bool
    {
        return true;
    }

    // Regras de validação. Se algo falhar, o Laravel responde 422 sozinho.
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],  // exige password_confirmation igual
        ];
    }
}
