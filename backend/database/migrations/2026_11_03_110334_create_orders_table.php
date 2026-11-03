<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();   // sem cascade: não apagamos histórico de pedidos

            // pending (aguardando pagamento), paid, shipped, delivered, cancelled
            $table->string('status')->default('pending')->index();

            // Totais calculados no servidor, em centavos.
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('shipping_cents');
            $table->unsignedInteger('total_cents');

            // Cópia do endereço no momento da compra.
            // Se o usuário editar ou apagar o endereço depois, o pedido não muda.
            $table->string('shipping_street');
            $table->string('shipping_number');
            $table->string('shipping_complement')->nullable();
            $table->string('shipping_district');
            $table->string('shipping_city');
            $table->string('shipping_state', 2);
            $table->string('shipping_zip_code', 9);

            $table->timestamp('paid_at')->nullable();   // preenchido quando o pagamento simulado é confirmado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
