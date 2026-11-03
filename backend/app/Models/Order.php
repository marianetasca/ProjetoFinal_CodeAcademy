<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'subtotal_cents',
        'shipping_cents',
        'total_cents',
        'shipping_street',
        'shipping_number',
        'shipping_complement',
        'shipping_district',
        'shipping_city',
        'shipping_state',
        'shipping_zip_code',
        'paid_at',
    ];

    // Ao ler, 'status' vira um OrderStatus (enum), não uma string solta.
    // Ao gravar, o Laravel converte de volta para o texto.
    protected $casts = [
        'status'  => OrderStatus::class,
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
