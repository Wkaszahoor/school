<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('principal_leave_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['leave_request_id', 'principal_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('principal_leave_reads'); }
};
