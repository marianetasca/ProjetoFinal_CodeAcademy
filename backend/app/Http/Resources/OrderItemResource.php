<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'product_name'     => $this->product_name,        // nome congelado
            'unit_price_cents' => $this->unit_price_cents,    // preço congelado
            'quantity'         => $this->quantity,
            'subtotal_cents'   => $this->unit_price_cents * $this->quantity,
        ];
    }
}
