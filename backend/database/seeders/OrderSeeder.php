<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $ana   = User::where('email', 'ana@loja.com')->first();
        $bruno = User::where('email', 'bruno@loja.com')->first();
        $carla = User::where('email', 'carla@loja.com')->first();

        // Um endereço para cada cliente (aparece na tela de perfil)
        $enderecoAna   = $this->endereco($ana, 'Rua das Flores', '123');
        $enderecoBruno = $this->endereco($bruno, 'Avenida Brasil', '456');
        $enderecoCarla = $this->endereco($carla, 'Rua XV de Novembro', '789');

        // Um pedido para cada um dos 5 status. Itens: [nome do produto, quantidade]
        $this->criarPedido($ana,   $enderecoAna,   OrderStatus::Pending,   [['Jiboia', 2], ['Vaso de barro 15cm', 2]]);
        $this->criarPedido($ana,   $enderecoAna,   OrderStatus::Paid,      [['Samambaia', 1]]);
        $this->criarPedido($bruno, $enderecoBruno, OrderStatus::Shipped,   [['Hortênsia', 3], ['Adubo NPK 500g', 1]]);
        $this->criarPedido($bruno, $enderecoBruno, OrderStatus::Delivered, [['Tesoura de poda', 1]]);
        $this->criarPedido($carla, $enderecoCarla, OrderStatus::Cancelled, [['Zamioculca', 1]]);
    }

    private function endereco(User $user, string $rua, string $numero): Address
    {
        return $user->addresses()->create([
            'street' => $rua,
            'number' => $numero,
            'complement' => null,
            'district' => 'Centro',
            'city' => 'Guarapuava',
            'state' => 'PR',
            'zip_code' => '85010-000',
        ]);
    }

    private function criarPedido(User $user, Address $endereco, OrderStatus $status, array $itens): void
    {
        // Calcula o subtotal com os preços atuais dos produtos
        $subtotal = 0;
        foreach ($itens as [$nome, $quantidade]) {
            $subtotal += Product::where('name', $nome)->value('price_cents') * $quantidade;
        }

        // Mesma regra de frete do OrderService: R$ 15,00, grátis a partir de R$ 200,00
        $frete = $subtotal >= 20000 ? 0 : 1500;

        // Pagos, enviados e entregues já passaram pelo pagamento
        $foiPago = in_array($status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered]);

        $pedido = Order::create([
            'user_id'             => $user->id,
            'status'              => $status,
            'subtotal_cents'      => $subtotal,
            'shipping_cents'      => $frete,
            'total_cents'         => $subtotal + $frete,
            'shipping_street'     => $endereco->street,
            'shipping_number'     => $endereco->number,
            'shipping_complement' => $endereco->complement,
            'shipping_district'   => $endereco->district,
            'shipping_city'       => $endereco->city,
            'shipping_state'      => $endereco->state,
            'shipping_zip_code'   => $endereco->zip_code,
            'paid_at'             => $foiPago ? now() : null,
        ]);

        foreach ($itens as [$nome, $quantidade]) {
            $produto = Product::where('name', $nome)->first();

            // Nome e preço congelados, como no checkout de verdade
            $pedido->items()->create([
                'product_id'       => $produto->id,
                'product_name'     => $produto->name,
                'unit_price_cents' => $produto->price_cents,
                'quantity'         => $quantidade,
            ]);

            // Pedido cancelado devolve o estoque, então só os outros dão baixa
            if ($status !== OrderStatus::Cancelled) {
                $produto->decrement('stock', $quantidade);
            }
        }

        if ($foiPago) {
            Payment::create([
                'order_id'     => $pedido->id,
                'amount_cents' => $pedido->total_cents,
                'status'       => 'paid',
                'paid_at'      => now(),
            ]);
        }
    }
}
