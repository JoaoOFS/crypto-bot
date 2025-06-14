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
        Schema::create('backtests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('portfolio_id')->constrained()->onDelete('cascade');
            $table->foreignId('trading_strategy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamp('start_date')->useCurrent();
            $table->timestamp('end_date')->useCurrent();
            $table->decimal('initial_balance', 20, 8);
            $table->decimal('final_balance', 20, 8);
            $table->decimal('total_profit_loss', 20, 8);
            $table->decimal('win_rate', 5, 2);
            $table->integer('total_trades');
            $table->integer('winning_trades');
            $table->integer('losing_trades');
            $table->json('parameters');
            $table->json('results')->nullable();
            $table->string('status'); // running, completed, failed
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtests');
    }
};
