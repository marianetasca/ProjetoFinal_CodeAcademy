<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    // Lista com busca, filtro, ordenação e paginação.
    // $includeInactive = true só é usado pelo painel do admin.
    public function list(array $filters, bool $includeInactive = false): LengthAwarePaginator
    {
        $query = Product::with('category');//eager loading

        if (! $includeInactive) {
            $query->active();   // produtos inativos não aparecem no catálogo
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        match ($filters['sort'] ?? 'latest') {
            'price_asc'  => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            default      => $query->orderByDesc('id'),   // mais recentes primeiro
        };

        // 12 por página. withQueryString() mantém search/sort nos links das páginas.
        return $query->paginate(12)->withQueryString();///para nao perder o filtro quando for feita a busca
    }

    // Detalhe público: produto inativo ou inexistente dá 404 (findOrFail).
    public function find(int $id): Product
    {
        return Product::active()->with('category')->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $data = $this->saveImage($data);

        return Product::create($data)->load('category');
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);

        // Se veio uma imagem nova, apaga a antiga do disco antes de trocar.
        if (isset($data['image']) && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->update($this->saveImage($data));

        return $product->load('category');
    }

    // Exclusão lógica (soft delete): a linha continua no banco, então pedidos antigos não quebram.
    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }

    // Guarda o arquivo em storage/app/public/products e troca 'image' por 'image_path'.
    private function saveImage(array $data): array
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image_path'] = $data['image']->store('products', 'public');
        }

        unset($data['image']);   // 'image' não é coluna do banco

        return $data;
    }
}
