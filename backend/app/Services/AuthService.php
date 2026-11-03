<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    // Cria o usuário e já devolve um token de acesso.
    public function register(array $data): array
    {
        $user = User::create($data);   // a senha é criptografada pelo cast 'hashed' do Model

        return [
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    // Confere e-mail e senha. Se estiverem certos, devolve o usuário e um token.
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // A mensagem é a mesma para e-mail inexistente e senha errada,
        // assim ninguém descobre quais e-mails estão cadastrados.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        return [
            'user'  => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    // Apaga o token usado nesta requisição. Depois disso ele deixa de valer.
    public function logout(Request $request): void
    {
        $request->user()->currentAccessToken()->delete();
    }
}
