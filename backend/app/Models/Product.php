<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;   // delete() passa a preencher deleted_at em vez de apagar a linha

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price_cents',
        'stock',
        'image_path',
        'active',
    ];

    // Converte os tipos ao ler do banco (o MySQL devolve boolean como 0/1)
    protected $casts = ['active' => 'boolean'];

    // Um produto pertence a uma categoria
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Escopo reutilizável: Product::active()->get() traz só os produtos ativos.
    // O catálogo público vai usar isso.
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
