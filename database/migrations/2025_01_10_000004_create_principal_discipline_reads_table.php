<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('principal_discipline_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_record_id')->constrained('discipline_records')->cascadeOnDelete();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['discipline_record_id', 'principal_id'], 'uniq_discipline_read');
        });
    }
    public function down(): void { Schema::dropIfExists('principal_discipline_reads'); }
};
