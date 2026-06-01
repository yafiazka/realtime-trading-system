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
        if (!Schema::hasTable('trades')) {
            Schema::create('trades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('maker_order_id')->constrained('orders')->restrictOnDelete();
                $table->foreignId('taker_order_id')->constrained('orders')->restrictOnDelete();
                $table->decimal('price', 18, 8);
                $table->decimal('quantity', 18, 8);
                $table->timestamp('executed_at')->useCurrent();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('trades')) {
            Schema::dropIfExists('trades');
        }
    }
};
