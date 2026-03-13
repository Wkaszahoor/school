<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('principal_kpi_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->date('kpi_date');
            $table->string('category', 80);
            $table->string('metric', 200);
            $table->decimal('target', 10, 2)->nullable();
            $table->decimal('actual', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('principal_kpi_entries'); }
};
