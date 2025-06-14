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
        Schema::create('backtest_equity_curves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backtest_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp')->useCurrent();
            $table->decimal('equity', 20, 8);
            $table->decimal('drawdown', 20, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_equity_curves');
    }
};
