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
        if (!Schema::hasTable('trading_pairs')) {
            Schema::create('trading_pairs', function (Blueprint $table) {
                $table->id();
                $table->string('base_asset', 10);
                $table->string('quote_asset', 10);
                $table->decimal('tick_size', 18, 8)->default(0.01);
                $table->decimal('min_quantity', 18, 8)->default(0.00001);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['base_asset', 'quote_asset']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('trading_pairs')) {
            Schema::dropIfExists('trading_pairs');
        }
    }
};
