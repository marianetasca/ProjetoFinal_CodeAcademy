<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    // Regra de frete (documentada no README): fixo, e grátis a partir de um valor.
    private const SHIPPING_CENTS = 1500;             // R$ 15,00
    private const FREE_SHIPPING_FROM_CENTS = 20000;  // R$ 200,00

    // Cria o pedido a partir do carrinho do usuário.
    public function create(User $user, int $addressId): Order
    {
        // Transação: se qualquer coisa falhar lá dentro, NADA é gravado.
        return DB::transaction(function () use ($user, $addressId) {

            // Endereço só entre os do próprio usuário (de outra pessoa dá 404)
            $address = $user->addresses()->findOrFail($addressId);

            $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

            // Carrinho vazio: 422
            if (! $cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['O carrinho está vazio.'],
                ]);
            }

            // lockForUpdate trava as linhas dos produtos até o fim da transação.
            // Assim, duas compras ao mesmo tempo não vendem o mesmo estoque duas vezes.
            $products = Product::whereIn('id', $cart->items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 1ª passada: só CONFERE tudo, sem gravar nada
            $errors = [];
            $subtotal = 0;

            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);   // null se foi apagado (soft delete)
                $name = $item->product->name;                    // o relacionamento enxerga apagados

                if (! $product || ! $product->active) {
                    $errors[] = "{$name} não está mais disponível.";
                } elseif ($product->stock < $item->quantity) {
                    $errors[] = "{$name}: estoque insuficiente (disponível: {$product->stock}).";
                } else {
                    // O preço vem do BANCO, nunca do cliente
                    $subtotal += $product->price_cents * $item->quantity;
                }
            }

            if ($errors) {
                throw ValidationException::withMessages(['items' => $errors]);
            }

            $shipping = $subtotal >= self::FREE_SHIPPING_FROM_CENTS ? 0 : self::SHIPPING_CENTS;

            // 2ª passada: agora grava
            $order = Order::create([
                'user_id'             => $user->id,
                'status'              => OrderStatus::Pending,   // aguardando pagamento
                'subtotal_cents'      => $subtotal,
                'shipping_cents'      => $shipping,
                'total_cents'         => $subtotal + $shipping,
                // Cópia do endereço: se ele mudar depois, o pedido não muda
                'shipping_street'     => $address->street,
                'shipping_number'     => $address->number,
                'shipping_complement' => $address->complement,
                'shipping_district'   => $address->district,
                'shipping_city'       => $address->city,
                'shipping_state'      => $address->state,
                'shipping_zip_code'   => $address->zip_code,
            ]);

            foreach ($cart->items as $item) {
                $product = $products->get($item->product_id);

                // Nome e preço CONGELADOS neste momento
                $order->items()->create([
                    'product_id'       => $product->id,
                    'product_name'     => $product->name,
                    'unit_price_cents' => $product->price_cents,
                    'quantity'         => $item->quantity,
                ]);

                // Baixa de estoque
                $product->decrement('stock', $item->quantity);
            }

            // Esvazia o carrinho (o carrinho em si continua existindo)
            $cart->items()->delete();

            return $order->load('items');
        });
    }

    // Lista os pedidos do próprio usuário, do mais novo para o mais antigo
    public function listForUser(User $user): LengthAwarePaginator
    {
        return $user->orders()->orderByDesc('id')->paginate(10);
    }

    // Detalhe: só o dono do pedido ou um admin pode ver
    public function find(User $user, int $id): Order
    {
        $order = Order::with('items')->findOrFail($id);

        if ($order->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'Você não tem permissão para ver este pedido.');
        }

        return $order;
    }
}
