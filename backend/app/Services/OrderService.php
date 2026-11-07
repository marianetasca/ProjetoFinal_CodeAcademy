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
use App\Models\Payment;

class OrderService
{
    // Regra de frete (documentada no README): fixo, e grátis a partir de um valor.
    public const SHIPPING_CENTS = 1500;             // R$ 15,00
    public const FREE_SHIPPING_FROM_CENTS = 20000;  // R$ 200,00

    // A regra do frete fica só aqui: o carrinho usa para MOSTRAR e o pedido usa para COBRAR.
    public static function shippingFor(int $subtotalCents): int
    {
        return $subtotalCents >= self::FREE_SHIPPING_FROM_CENTS ? 0 : self::SHIPPING_CENTS;
    }

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

            $shipping = self::shippingFor($subtotal);

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

    // Pagamento simulado: só o DONO pode pagar.
    public function pay(User $user, int $id): Order
    {
        return DB::transaction(function () use ($user, $id) {
            // lockForUpdate: se a pessoa clicar duas vezes, o segundo clique espera o primeiro terminar
            $order = Order::with('items')->lockForUpdate()->findOrFail($id);

            if ($order->user_id !== $user->id) {
                abort(403, 'Somente o dono do pedido pode pagá-lo.');
            }

            // Só pode pagar quem está aguardando pagamento (usa a regra do enum)
            if (! $order->status->canTransitionTo(OrderStatus::Paid)) {
                abort(409, 'Este pedido não está aguardando pagamento.');
            }

            // Registro do pagamento simulado (sem nenhum dado de cartão)
            Payment::create([
                'order_id'     => $order->id,
                'amount_cents' => $order->total_cents,
                'status'       => 'paid',
                'paid_at'      => now(),
            ]);

            $order->update([
                'status'  => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            return $order;
        });
    }

    // Cancelamento: dono ou admin. Devolve o estoque.
    public function cancel(User $user, int $id): Order
    {
        return DB::transaction(function () use ($user, $id) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($id);

            if ($order->user_id !== $user->id && ! $user->isAdmin()) {
                abort(403, 'Você não tem permissão para cancelar este pedido.');
            }

            // Só pedido aguardando pagamento pode ser cancelado
            if (! $order->status->canTransitionTo(OrderStatus::Cancelled)) {
                abort(409, 'Somente pedidos aguardando pagamento podem ser cancelados.');
            }

            // Devolve a quantidade de cada item ao estoque.
            // withTrashed(): mesmo que o produto tenha sido "apagado" (soft delete), o estoque volta.
            foreach ($order->items as $item) {
                Product::withTrashed()
                    ->where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            $order->update(['status' => OrderStatus::Cancelled]);

            return $order;
        });
    }

    // ----- Admin -----

    // Todos os pedidos da loja, com filtro opcional por status
    public function listAll(?string $status): LengthAwarePaginator
    {
        return Order::with('user')
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    // Avança o status respeitando as transições válidas
    public function updateStatus(int $id, OrderStatus $next): Order
    {
        $order = Order::findOrFail($id);

        if (! $order->status->canTransitionTo($next)) {
            abort(409, "Transição inválida: {$order->status->value} → {$next->value}.");
        }

        $order->update(['status' => $next]);

        return $order->load('items', 'user');
    }
}
