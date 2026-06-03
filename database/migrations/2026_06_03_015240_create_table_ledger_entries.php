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
        if (!Schema::hasTable('ledger_entries')) {
            Schema::create('ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20);
                $table->decimal('amount', 18, 8);
                $table->decimal('balance_after', 18, 8);
                $table->decimal('locked_after', 18, 8);
                $table->nullableMorphs('reference');

                $table->timestamp('created_at')->useCurrent();
                $table->index('wallet_id');
                $table->index('reference_type', 'reference_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ledger_entries')) {
            Schema::dropIfExists('ledger_entries');
        }
    }
};
