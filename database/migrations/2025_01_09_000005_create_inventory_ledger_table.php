<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('txn_type', ['in', 'out'])->default('in');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('balance_after')->default(0);
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('item_id');
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_ledger'); }
};
