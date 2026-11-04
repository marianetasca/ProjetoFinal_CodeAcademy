<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// O Laravel coloca /api na frente de todas as rotas deste arquivo.

// ---------- Autenticação (/api/auth/...) ----------
Route::prefix('auth')->group(function () {

    // Públicas. O throttle limita a 6 tentativas por minuto (contra força bruta).
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Exigem sessão ativa (sem ela, respondem 401).
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// ---------- Públicas: catálogo ----------
Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show'])->whereNumber('product');

// ---------- Públicas: carrinho (vale para visitante e para usuário logado) ----------
Route::get('cart', [CartController::class, 'show']);
Route::post('cart/items', [CartController::class, 'addItem']);
Route::patch('cart/items/{item}', [CartController::class, 'updateItem'])->whereNumber('item');
Route::delete('cart/items/{item}', [CartController::class, 'removeItem'])->whereNumber('item');

// ---------- Exigem login (sem sessão, respondem 401) ----------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('addresses', [AddressController::class, 'store']);
    Route::put('addresses/{address}', [AddressController::class, 'update'])->whereNumber('address');
    Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->whereNumber('address');

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order');
});

// ---------- Admin (/api/admin/...): sessão ativa E papel admin ----------
// Cliente logado recebe 403; sem login recebe 401.
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category');

    Route::get('products', [ProductController::class, 'adminIndex']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update'])->whereNumber('product');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->whereNumber('product');
});
