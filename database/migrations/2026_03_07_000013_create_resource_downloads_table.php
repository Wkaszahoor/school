<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resource_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained('teaching_resources')->cascadeOnDelete();
            $table->foreignId('downloaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->enum('status', ['completed', 'failed', 'interrupted'])->default('completed');
            $table->timestamps();

            $table->index('resource_id');
            $table->index('downloaded_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_downloads');
    }
};
