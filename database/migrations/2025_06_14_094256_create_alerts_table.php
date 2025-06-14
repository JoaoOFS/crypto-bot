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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('portfolio_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->string('type');
            $table->string('symbol')->nullable();
            $table->string('condition');
            $table->decimal('value', 20, 8);
            $table->boolean('is_triggered')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('alerts');
    }
};
