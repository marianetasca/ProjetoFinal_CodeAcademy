<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'      => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            // Ordenações aceitas: mais recentes (padrão), preço crescente e decrescente
            'sort'        => ['nullable', 'in:latest,price_asc,price_desc'],
        ];
    }
}
