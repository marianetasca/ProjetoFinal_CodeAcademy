<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    // Visitante: user_id nulo e session_id preenchido. Usuário logado: o contrário.
    protected $fillable = ['user_id', 'session_id'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany  { return $this->hasMany(CartItem::class); }

    // Quantidade total de unidades (é o número que aparece no ícone do carrinho)
    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    // Total em centavos, calculado aqui no servidor.
    // Itens indisponíveis ficam de fora, para não cobrar algo que não dá para comprar.
    public function totalCents(): int
    {
        return (int) $this->items
            ->filter(fn ($item) => $item->isAvailable())
            ->sum(fn ($item) => $item->subtotalCents());
    }
}
