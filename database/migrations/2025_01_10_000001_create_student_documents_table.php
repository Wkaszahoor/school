<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('doc_type', 60);
            $table->string('doc_title', 200);
            $table->unsignedSmallInteger('version_no')->default(1);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('student_documents'); }
};
