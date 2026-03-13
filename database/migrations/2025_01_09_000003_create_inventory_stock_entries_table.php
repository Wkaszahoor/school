<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('supplier', 200)->nullable();
            $table->date('entry_date');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_stock_entries'); }
};
