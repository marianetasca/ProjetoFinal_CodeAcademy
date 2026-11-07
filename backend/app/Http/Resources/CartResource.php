<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\OrderService;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = $this->totalCents();

        // Sem itens disponíveis não há o que enviar, então não há frete
        $frete = $subtotal > 0 ? OrderService::shippingFor($subtotal) : 0;

        return [
            'items'             => CartItemResource::collection($this->items),
            'items_count'       => $this->itemsCount(),
            'total_cents'       => $subtotal,
            'shipping_cents'    => $frete,
            'grand_total_cents' => $subtotal + $frete,
        ];
    }
}
