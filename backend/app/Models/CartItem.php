<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'quantity'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    // withTrashed(): mesmo que o produto tenha sido "apagado" (soft delete),
    // ainda conseguimos carregá-lo para sinalizar o item como indisponível.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
