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
        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('asset', 20);
                $table->decimal('balance', 18, 8)->default(0);
                $table->decimal('locked_balance', 18, 8)->default(0);

                $table->timestamps();
                $table->unique(['user_id', 'asset']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wallets')) {
            Schema::dropIfExists('table_wallets');
        }
    }
};
