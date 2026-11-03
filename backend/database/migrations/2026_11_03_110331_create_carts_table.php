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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            // Carrinho de usuário logado: user_id preenchido.
            // unique() garante no máximo UM carrinho por usuário.
            // nullOnDelete não se aplica aqui; se o usuário for apagado, o carrinho vai junto.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();

            // Carrinho de visitante: user_id nulo e token preenchido.
            // O token é gerado pelo servidor (aleatório) e guardado pelo frontend.
            $table->string('token', 64)->nullable()->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
