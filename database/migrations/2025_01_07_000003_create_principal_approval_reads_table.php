<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('principal_approval_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_lock_group_id')->constrained('result_lock_groups')->cascadeOnDelete();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['result_lock_group_id', 'principal_id'], 'uniq_approval_read');
        });
    }
    public function down(): void { Schema::dropIfExists('principal_approval_reads'); }
};
