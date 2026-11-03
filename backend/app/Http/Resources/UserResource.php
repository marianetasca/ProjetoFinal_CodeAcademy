<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    // Define exatamente quais campos do usuário aparecem na resposta.
    // A senha nunca aparece, porque não está nesta lista.
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,   // o frontend usa para mostrar ou esconder o painel do admin
        ];
    }
}
