<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // O Laravel entrega o CartService pronto (injeção de dependência)
    public function __construct(
        private CartService $cartService
    ) {}

    public function register(array $data, Request $request): array
    {
        // 1) Guarda o id da sessão ANTES de logar: é ele que identifica o carrinho de visitante
        $oldSessionId = $request->session()->getId();

        $user = User::create($data);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // 2) Funde o carrinho do visitante no da conta
        $warnings = $this->cartService->mergeGuestCart($oldSessionId, $user);

        return ['user' => $user, 'cart_warnings' => $warnings];
    }

    public function login(array $credentials, Request $request): array
    {
        $oldSessionId = $request->session()->getId();   // antes do attempt, que troca o id

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();
        $warnings = $this->cartService->mergeGuestCart($oldSessionId, $user);

        return ['user' => $user, 'cart_warnings' => $warnings];
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
