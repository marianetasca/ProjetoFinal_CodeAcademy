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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // unique() faz a relação ser 1:1: cada pedido tem no máximo um pagamento.
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedInteger('amount_cents');
            $table->string('status');                    // ex.: 'paid'
            $table->timestamp('paid_at')->nullable();    // data e hora do pagamento simulado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
