<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // forceCreate ignora o $fillable. É a única forma de gravar o 'role',
        // que ficou fora do fillable de propósito (ninguém vira admin pelo register).
        User::forceCreate([
            'name'     => 'Administrador',
            'email'    => 'admin@loja.com',
            'password' => 'password',   // o cast 'hashed' criptografa sozinho
            'role'     => 'admin',
        ]);

        // Três clientes (o role 'customer' vem do padrão da migration)
        foreach (['Ana', 'Bruno', 'Carla'] as $nome) {
            User::create([
                'name'     => $nome,
                'email'    => strtolower($nome) . '@loja.com',
                'password' => 'password',
            ]);
        }
    }
}
