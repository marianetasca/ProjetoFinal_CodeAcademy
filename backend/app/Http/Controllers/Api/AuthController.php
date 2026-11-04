<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // O Laravel entrega o AuthService pronto (injeção de dependência).
    public function __construct(
        private AuthService $authService
    ) {}

    // POST /api/auth/register
    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated(), $request);

        return (new UserResource($result['user']))
            ->additional(['cart_warnings' => $result['cart_warnings']])   // lista de avisos da fusão
            ->response()
            ->setStatusCode(201);
    }

    // POST /api/auth/login
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated(), $request);

        return (new UserResource($result['user']))
            ->additional(['cart_warnings' => $result['cart_warnings']]);
    }

    // GET /api/auth/me  -> devolve o usuário dono do token
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->noContent();   // 204: deu certo, sem conteúdo para devolver
    }
}
