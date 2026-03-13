<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('parent_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_id')->nullable()->constrained('discipline_records')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('meeting_date');
            $table->string('meeting_title', 200);
            $table->string('attendees', 500)->nullable();
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('parent_meetings'); }
};
