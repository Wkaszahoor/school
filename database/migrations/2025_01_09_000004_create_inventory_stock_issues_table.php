<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_stock_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('issued_to_type', ['teacher', 'class', 'other'])->default('other');
            $table->foreignId('issued_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_to_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('issue_date');
            $table->text('note')->nullable();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_stock_issues'); }
};
