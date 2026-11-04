<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Categoria => lista de [nome, preço em centavos, estoque]
        $catalogo = [
            'Plantas para ambientes internos' => [
                ['Jiboia', 3990, 25],
                ['Samambaia', 4590, 18],
                ['Espada-de-são-jorge', 5490, 12],
                ['Zamioculca', 7990, 8],
                ['Costela-de-adão', 8990, 6],
                ['Lírio-da-paz', 4990, 15],
                ['Pacová', 6490, 10],
                ['Orquídea Phalaenopsis', 11990, 0],   // estoque zero (para testar indisponível)
            ],
            'Plantas para ambientes externos' => [
                ['Lavanda', 3490, 20],
                ['Hortênsia', 6990, 9],
                ['Azaleia', 5990, 11],
                ['Buganvília', 7490, 7],
                ['Palmeira ráfia', 15990, 4],
                ['Jasmim-manga', 8490, 6],
                ['Alecrim', 1990, 30],
                ['Manjericão', 1490, 40],
            ],
            'Vasos e cachepôs' => [
                ['Vaso de cerâmica 20cm', 4990, 14],
                ['Vaso de barro 15cm', 1990, 50],
                ['Cachepô de vime', 5990, 10],
                ['Cachepô de cimento', 4490, 12],
                ['Vaso autoirrigável', 3990, 20],
                ['Suporte para vasos', 8990, 5],
            ],
            'Ferramentas de jardinagem' => [
                ['Pá de jardinagem', 2490, 25],
                ['Tesoura de poda', 5990, 10],
                ['Regador 2,5L', 3490, 18],
                ['Borrifador', 1890, 30],
                ['Luvas de jardinagem', 2290, 22],
            ],
            'Adubos' => [
                ['Húmus de minhoca 1kg', 1590, 35],
                ['Adubo NPK 500g', 1990, 28],
                ['Farinha de osso 500g', 1790, 20],
                ['Terra vegetal 5kg', 2290, 40],
                ['Fertilizante líquido', 1290, 26],
            ],
        ];

        foreach ($catalogo as $categoria => $produtos) {
            $categoriaId = Category::where('name', $categoria)->value('id');

            foreach ($produtos as [$nome, $preco, $estoque]) {
                Product::create([
                    'category_id' => $categoriaId,
                    'name'        => $nome,
                    'description' => "Descrição de exemplo: {$nome}.",
                    'price_cents' => $preco,
                    'stock'       => $estoque,
                ]);
            }
        }

        // Um produto inativo (não deve aparecer no catálogo público)
        Product::where('name', 'Palmeira ráfia')->update(['active' => false]);
    }
}
