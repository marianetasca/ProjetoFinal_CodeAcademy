<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService
{
    public function listAll(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function create(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);   // o slug é gerado a partir do nome

        return Category::create($data);
    }

    public function update(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);   // não achou = 404 automático
        $category->name = $data['name'];
        $category->slug = Str::slug($data['name']);
        $category->save();

        return $category;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        // Regra: não pode excluir categoria que tem produtos.
        // withTrashed() também conta produtos "apagados" (soft delete),
        // porque eles continuam no banco e o banco bloquearia a exclusão.
        if ($category->products()->withTrashed()->exists()) {
            abort(409, 'Não é possível excluir uma categoria que possui produtos.');
        }

        $category->delete();
    }
}
