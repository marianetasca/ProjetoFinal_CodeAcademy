<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'zip_code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
