<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // quem protege é o middleware admin, na rota
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:1'],   // preço em centavos (R$ 39,90 = 3990)
            'stock'       => ['required', 'integer', 'min:0'],
            'active'      => ['sometimes', 'boolean'],            // enviar 1 ou 0
            // Imagem opcional, até 2 MB, só estes formatos (SVG fica de fora de propósito)
            'image'       => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
