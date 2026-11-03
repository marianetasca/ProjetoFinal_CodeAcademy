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

            /// Id da sessão do visitante. Preenchido só quando user_id é nulo.
            $table->string('session_id')->nullable()->unique();

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
