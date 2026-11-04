<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(
        private AddressService $addressService
    ) {}

    // GET /api/addresses
    public function index(Request $request)
    {
        return AddressResource::collection($this->addressService->list($request->user()));
    }

    // POST /api/addresses
    public function store(StoreAddressRequest $request)
    {
        $address = $this->addressService->create($request->user(), $request->validated());

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    // PUT /api/addresses/{id}
    public function update(UpdateAddressRequest $request, int $id)
    {
        return new AddressResource($this->addressService->update($request->user(), $id, $request->validated()));
    }

    // DELETE /api/addresses/{id}
    public function destroy(Request $request, int $id)
    {
        $this->addressService->delete($request->user(), $id);

        return response()->noContent();
    }
}
