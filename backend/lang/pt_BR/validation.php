<?php

// Mensagens de validação em português.
// Ficam em backend/lang/pt_BR/validation.php e passam a valer com APP_LOCALE=pt_BR no .env.
// :attribute vira o nome do campo (veja a lista "attributes" no final do arquivo).

return [
    'boolean'   => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'email'     => 'O campo :attribute deve ser um e-mail válido.',
    'enum'      => 'O valor escolhido para :attribute é inválido.',
    'exists'    => 'O valor escolhido para :attribute é inválido.',
    'file'      => 'O campo :attribute deve ser um arquivo.',
    'in'        => 'O valor escolhido para :attribute é inválido.',
    'integer'   => 'O campo :attribute deve ser um número inteiro.',
    'mimes'     => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'regex'     => 'O formato de :attribute é inválido.',
    'required'  => 'O campo :attribute é obrigatório.',
    'string'    => 'O campo :attribute deve ser um texto.',
    'unique'    => 'Este valor de :attribute já está em uso.',

    // Regras que mudam conforme o tipo do campo (número, texto ou arquivo)
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'file'    => 'O arquivo de :attribute não pode ter mais que :max kilobytes.',
        'string'  => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'file'    => 'O arquivo de :attribute deve ter no mínimo :min kilobytes.',
        'string'  => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'size' => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'file'    => 'O arquivo de :attribute deve ter :size kilobytes.',
        'string'  => 'O campo :attribute deve ter exatamente :size caracteres.',
    ],

    'custom' => [],

    // Nomes amigáveis dos campos usados nas mensagens
    'attributes' => [
        'name'                  => 'nome',
        'email'                 => 'e-mail',
        'password'              => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'product_id'            => 'produto',
        'quantity'              => 'quantidade',
        'address_id'            => 'endereço',
        'street'                => 'rua',
        'number'                => 'número',
        'complement'            => 'complemento',
        'district'              => 'bairro',
        'city'                  => 'cidade',
        'state'                 => 'estado',
        'zip_code'              => 'CEP',
        'category_id'           => 'categoria',
        'description'           => 'descrição',
        'price_cents'           => 'preço',
        'stock'                 => 'estoque',
        'active'                => 'ativo',
        'image'                 => 'imagem',
        'status'                => 'status',
        'sort'                  => 'ordenação',
        'search'                => 'busca',
    ],
];
