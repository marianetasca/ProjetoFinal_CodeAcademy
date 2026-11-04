<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    // GET /api/orders
    public function index(Request $request)
    {
        return OrderResource::collection($this->orderService->listForUser($request->user()));
    }

    // POST /api/orders
    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->user(), $request->validated()['address_id']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    // GET /api/orders/{id}
    public function show(Request $request, int $id)
    {
        return new OrderResource($this->orderService->find($request->user(), $id));
    }

    // POST /api/orders/{id}/pay
    public function pay(Request $request, int $id)
    {
        return new OrderResource($this->orderService->pay($request->user(), $id));
    }

    // POST /api/orders/{id}/cancel
    public function cancel(Request $request, int $id)
    {
        return new OrderResource($this->orderService->cancel($request->user(), $id));
    }
}
