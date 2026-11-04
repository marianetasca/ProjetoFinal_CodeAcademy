<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    // GET /api/products (público)
    public function index(ProductIndexRequest $request)
    {
        return ProductResource::collection($this->productService->list($request->validated()));
    }

    // GET /api/products/{id} (público)
    public function show(int $id)
    {
        return new ProductResource($this->productService->find($id));
    }

    // GET /api/admin/products (admin vê também os inativos)
    public function adminIndex(ProductIndexRequest $request)
    {
        return ProductResource::collection($this->productService->list($request->validated(), true));
    }

    // POST /api/admin/products
    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    // PUT /api/admin/products/{id}
    public function update(UpdateProductRequest $request, int $id)
    {
        return new ProductResource($this->productService->update($id, $request->validated()));
    }

    // DELETE /api/admin/products/{id}
    public function destroy(int $id)
    {
        $this->productService->delete($id);

        return response()->noContent();
    }
}
