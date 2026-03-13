<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('class_stream_subject_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('stream_key', 40);
            $table->foreignId('group_id')->constrained('subject_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_id', 'stream_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('class_stream_subject_groups'); }
};
