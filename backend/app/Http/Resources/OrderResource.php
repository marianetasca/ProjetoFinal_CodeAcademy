<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status->value,   // pending, paid, shipped, delivered, cancelled
            'subtotal_cents' => $this->subtotal_cents,
            'shipping_cents' => $this->shipping_cents,
            'total_cents'    => $this->total_cents,
            'paid_at'        => $this->paid_at,
            'created_at'     => $this->created_at,
            'shipping_address' => [
                'street'     => $this->shipping_street,
                'number'     => $this->shipping_number,
                'complement' => $this->shipping_complement,
                'district'   => $this->shipping_district,
                'city'       => $this->shipping_city,
                'state'      => $this->shipping_state,
                'zip_code'   => $this->shipping_zip_code,
            ],
            // Só aparece quando os itens foram carregados (detalhe do pedido)
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // Só aparece quando o usuário foi carregado (lista do admin)
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
