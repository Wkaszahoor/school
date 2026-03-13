<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 200);
            $table->foreignId('category_id')->constrained('inventory_categories')->cascadeOnDelete();
            $table->string('unit', 30)->default('pcs');
            $table->unsignedSmallInteger('reorder_level')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_items'); }
};
