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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // restrictOnDelete: o banco IMPEDE excluir uma categoria que ainda tem produtos.
            // Isso cumpre a regra "impede excluir categoria com produtos vinculados".
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Dinheiro em centavos inteiros (R$ 19,90 vira 1990). Nunca usamos float para dinheiro.
            // unsigned = não aceita valor negativo.
            $table->unsignedInteger('price_cents');

            $table->unsignedInteger('stock')->default(0);   // quantidade em estoque
            $table->string('image_path')->nullable();       // caminho da imagem enviada
            $table->boolean('active')->default(true);       // false = oculto do catálogo

            // Exclusão lógica: "apagar" só preenche deleted_at, e a linha continua no banco.
            // Assim pedidos antigos que usam esse produto não quebram.
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
