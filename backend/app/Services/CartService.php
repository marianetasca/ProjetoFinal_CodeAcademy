<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    // Devolve o carrinho atual com os itens e produtos carregados.
    // Se a pessoa ainda não tem carrinho, devolve um vazio SEM gravar nada no banco
    // (assim não criamos uma linha para cada pessoa que só olha a loja).
    public function getCart(Request $request): Cart
    {
        $cart = $this->findCart($request);

        if (! $cart) {
            $cart = new Cart();
            $cart->setRelation('items', new Collection());

            return $cart;
        }

        return $cart->load('items.product');
    }

    public function addItem(Request $request, int $productId, int $quantity): Cart
    {
        // Produto inexistente ou inativo: 404
        $product = Product::active()->findOrFail($productId);

        if ($product->stock < 1) {
            throw ValidationException::withMessages([
                'product_id' => ['Produto sem estoque.'],
            ]);
        }

        $cart = $this->findOrCreateCart($request);

        // A dupla carrinho + produto é única: se já existe a linha, soma a quantidade.
        $item = $cart->items()->where('product_id', $product->id)->first();
        $newQuantity = ($item ? $item->quantity : 0) + $quantity;

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Estoque insuficiente. Disponível: {$product->stock}."],
            ]);
        }

        if ($item) {
            $item->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create(['product_id' => $product->id, 'quantity' => $newQuantity]);
        }

        return $this->getCart($request);
    }

    public function updateItem(Request $request, int $itemId, int $quantity): Cart
    {
        $item = $this->findOwnItem($request, $itemId);

        // Quantidade 0 remove o item
        if ($quantity === 0) {
            $item->delete();

            return $this->getCart($request);
        }

        if ($quantity > $item->product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Estoque insuficiente. Disponível: {$item->product->stock}."],
            ]);
        }

        $item->update(['quantity' => $quantity]);

        return $this->getCart($request);
    }

    public function removeItem(Request $request, int $itemId): Cart
    {
        $this->findOwnItem($request, $itemId)->delete();

        return $this->getCart($request);
    }

    // Funde o carrinho do visitante (identificado pelo id da sessão ANTIGA) no carrinho do usuário.
    // Devolve uma lista de avisos para mostrar ao usuário.
    public function mergeGuestCart(string $oldSessionId, User $user): array
    {
        $guestCart = Cart::where('session_id', $oldSessionId)->whereNull('user_id')->first();

        // Não havia carrinho de visitante: nada a fazer
        if (! $guestCart) {
            return [];
        }

        $warnings = [];

        // Transação: ou a fusão inteira acontece, ou nada é gravado
        DB::transaction(function () use ($guestCart, $user, &$warnings) {
            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

            foreach ($guestCart->items()->with('product')->get() as $guestItem) {
                $product = $guestItem->product;

                // Produto indisponível não pode entrar no carrinho (regra do carrinho)
                if (! $product || $product->trashed() || ! $product->active || $product->stock < 1) {
                    $name = $product?->name ?? 'Um produto';
                    $warnings[] = "{$name} está indisponível e não foi adicionado ao carrinho.";
                    continue;
                }

                // Esse produto já está no carrinho da conta?
                $userItem = $userCart->items()->where('product_id', $product->id)->first();

                // Regra: soma as quantidades
                $total = ($userItem ? $userItem->quantity : 0) + $guestItem->quantity;

                // Regra: limita ao estoque e avisa
                if ($total > $product->stock) {
                    $total = $product->stock;
                    $warnings[] = "A quantidade de {$product->name} foi ajustada para {$total} (estoque disponível).";
                }

                if ($userItem) {
                    $userItem->update(['quantity' => $total]);   // já existia: só atualiza
                } else {
                    $userCart->items()->create(['product_id' => $product->id, 'quantity' => $total]);
                }
            }

            // Descarta o carrinho de visitante. Os itens dele são apagados junto (cascade).
            $guestCart->delete();
        });

        return $warnings;
    }

    // ---------- Métodos internos ----------

    // Procura o carrinho: pelo usuário (se logado) ou pelo id da sessão (visitante).
    private function findCart(Request $request): ?Cart
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            return Cart::where('user_id', $user->id)->first();
        }

        return Cart::where('session_id', $request->session()->getId())->first();
    }

    // Igual ao anterior, mas cria o carrinho se ele ainda não existir.
    private function findOrCreateCart(Request $request): Cart
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        return Cart::firstOrCreate(['session_id' => $request->session()->getId()]);
    }

    // Busca o item DENTRO do carrinho da pessoa.
    // Se o id for de outro carrinho, dá 404: ninguém mexe no carrinho alheio.
    private function findOwnItem(Request $request, int $itemId)
    {
        $cart = $this->findCart($request);

        abort_if(! $cart, 404, 'Item não encontrado.');

        return $cart->items()->with('product')->findOrFail($itemId);
    }
}
