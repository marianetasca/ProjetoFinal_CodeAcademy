<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    // GET /api/admin/orders?status=paid
    public function index(OrderIndexRequest $request)
    {
        return OrderResource::collection(
            $this->orderService->listAll($request->validated()['status'] ?? null)
        );
    }

    // PATCH /api/admin/orders/{id}/status
    public function updateStatus(UpdateOrderStatusRequest $request, int $id)
    {
        // OrderStatus::from converte o texto ("shipped") para o enum
        $next = OrderStatus::from($request->validated()['status']);

        return new OrderResource($this->orderService->updateStatus($id, $next));
    }
}
