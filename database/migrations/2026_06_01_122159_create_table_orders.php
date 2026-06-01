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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('symbol', 20);
                $table->enum('side', ['BUY', 'SELL']);
                $table->enum('type', ['LIMIT', 'MARKET']);
                $table->decimal('price', 18, 8)->nullable();
                $table->decimal('quantity', 18, 8);
                $table->decimal('filled_quantity', 18, 8)->default(0);
                $table->enum('status', ['OPEN', 'PARTIALLY_FILLED', 'FILLED', 'CANCELED'])->default('OPEN');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::dropIfExists('orders');
        }
    }
};
