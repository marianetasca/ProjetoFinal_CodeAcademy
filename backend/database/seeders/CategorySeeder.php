<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (
            [
                'Plantas para ambientes internos',
                'Plantas para ambientes externos',
                'Vasos e cachepôs',
                'Ferramentas de jardinagem',
                'Adubos',
            ] as $nome
        ) {
            Category::create([
                'name' => $nome,
                'slug' => Str::slug($nome),   // "Vasos e cachepôs" vira "vasos-e-cachepos"
            ]);
        }
    }
}
