<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    // Campos que podem ser gravados com Payment::create([...])
    protected $fillable = ['order_id', 'amount_cents', 'status', 'paid_at'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    // Cada pagamento pertence a um pedido
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
