<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_role', 40);
            $table->string('user_name', 120)->nullable();
            $table->string('action', 80);
            $table->string('resource', 80);
            $table->string('reference_id', 120)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id');
            $table->index(['resource', 'action']);
            $table->index('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};
