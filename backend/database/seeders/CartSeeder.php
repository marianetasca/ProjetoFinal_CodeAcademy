<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        // Carrinho de VISITANTE: user_id nulo e session_id preenchido.
        // O id é fictício, só serve para existir um exemplo no banco.
        $visitante = Cart::create(['session_id' => 'seed-visitante-exemplo']);
        $visitante->items()->create(['product_id' => $this->id('Lavanda'), 'quantity' => 2]);
        $visitante->items()->create(['product_id' => $this->id('Regador 2,5L'), 'quantity' => 1]);

        // Carrinho de USUÁRIO (a Carla)
        $carla = User::where('email', 'carla@loja.com')->first();
        $carrinho = Cart::create(['user_id' => $carla->id]);
        $carrinho->items()->create(['product_id' => $this->id('Jiboia'), 'quantity' => 1]);
        $carrinho->items()->create(['product_id' => $this->id('Húmus de minhoca 1kg'), 'quantity' => 3]);
    }

    // Busca o id do produto pelo nome (mais seguro do que "chutar" o número do id)
    private function id(string $nome): int
    {
        return Product::where('name', $nome)->value('id');
    }
}
