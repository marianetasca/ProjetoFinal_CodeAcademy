<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AddressService
{
    public function list(User $user): Collection
    {
        return $user->addresses()->orderByDesc('id')->get();
    }

    public function create(User $user, array $data): Address
    {
        // Cria já ligado ao usuário (preenche o user_id sozinho)
        return $user->addresses()->create($data);
    }

    public function update(User $user, int $id, array $data): Address
    {
        // Procura SÓ entre os endereços do usuário: id de outra pessoa dá 404
        $address = $user->addresses()->findOrFail($id);
        $address->update($data);

        return $address;
    }

    public function delete(User $user, int $id): void
    {
        // Pode apagar sem medo: o pedido guarda uma CÓPIA do endereço
        $user->addresses()->findOrFail($id)->delete();
    }
}
