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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('portfolio_id')->constrained()->onDelete('cascade');
            $table->foreignId('trading_strategy_id')->constrained()->onDelete('cascade');
            $table->string('symbol');
            $table->string('type'); // buy, sell
            $table->decimal('price', 20, 8);
            $table->decimal('quantity', 20, 8);
            $table->decimal('total', 20, 8);
            $table->string('status'); // open, closed, cancelled
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('profit_loss', 20, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
