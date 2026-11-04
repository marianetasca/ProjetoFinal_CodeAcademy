<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

// Todas começam com /api/auth (o /api vem do Laravel, o /auth vem do prefix).
Route::prefix('auth')->group(function () {

    // Públicas. O throttle limita a 6 tentativas por minuto contra tentativas de força bruta.
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Protegidas: exigem um token válido (sem ele, responde 401).
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Público: qualquer pessoa vê as categorias
Route::get('categories', [CategoryController::class, 'index']);

// Admin: precisa de token válido (auth:sanctum) E do papel admin (admin)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update'])->whereNumber('category');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->whereNumber('category');

    Route::get('products', [ProductController::class, 'adminIndex']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update'])->whereNumber('product');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->whereNumber('product');
});


// Públicas
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show'])->whereNumber('product');
