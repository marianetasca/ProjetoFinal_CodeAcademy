<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // Cria o usuário e já o deixa logado (grava na sessão).
    public function register(array $data, Request $request): User
    {
        $user = User::create($data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();   // gera um id de sessão novo (evita fixação de sessão)

        return $user;
    }

    // Confere e-mail e senha. Se estiverem certos, abre a sessão.
    public function login(array $credentials, Request $request): User
    {
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        $request->session()->regenerate();

        return Auth::guard('web')->user();
    }

    // Encerra a sessão: o cookie antigo deixa de valer.
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
