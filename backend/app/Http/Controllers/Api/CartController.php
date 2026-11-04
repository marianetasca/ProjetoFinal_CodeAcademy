<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    // GET /api/cart
    public function show(Request $request)
    {
        return new CartResource($this->cartService->getCart($request));
    }

    // POST /api/cart/items
    public function addItem(AddCartItemRequest $request)
    {
        $data = $request->validated();
        $cart = $this->cartService->addItem($request, $data['product_id'], $data['quantity']);

        return (new CartResource($cart))->response()->setStatusCode(201);
    }

    // PATCH /api/cart/items/{id}
    public function updateItem(UpdateCartItemRequest $request, int $id)
    {
        $cart = $this->cartService->updateItem($request, $id, $request->validated()['quantity']);

        return new CartResource($cart);
    }

    // DELETE /api/cart/items/{id}
    public function removeItem(Request $request, int $id)
    {
        return new CartResource($this->cartService->removeItem($request, $id));
    }
}
