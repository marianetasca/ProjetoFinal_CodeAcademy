<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'items'       => CartItemResource::collection($this->items),
            'items_count' => $this->itemsCount(),
            'total_cents' => $this->totalCents(),
        ];
    }
}
