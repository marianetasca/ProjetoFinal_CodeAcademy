<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'quantity'         => $this->quantity,
            'unit_price_cents' => $this->product->price_cents,
            'subtotal_cents'   => $this->subtotalCents(),
            'available'        => $this->isAvailable(),   // false = o frontend sinaliza o item
            'product'          => new ProductResource($this->product),
        ];
    }
}
