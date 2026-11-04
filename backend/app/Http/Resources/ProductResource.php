<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'stock'       => $this->stock,
            'active'      => $this->active,
            // Facilita o frontend: indisponível quando o estoque é zero
            'available'   => $this->active && $this->stock > 0,
            // Caminho relativo: funciona em qualquer endereço, pois passa pelo proxy
            'image_url'   => $this->image_path ? '/storage/' . $this->image_path : null,
            // Só aparece se a categoria foi carregada (with('category') no service)
            'category'    => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
