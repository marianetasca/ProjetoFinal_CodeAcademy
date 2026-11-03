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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();  // apagou o carrinho, apaga os itens
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            // Regra obrigatória: o mesmo produto não pode aparecer duas vezes no mesmo carrinho.
            // Adicionar de novo deve SOMAR a quantidade na linha existente.
            $table->unique(['cart_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
