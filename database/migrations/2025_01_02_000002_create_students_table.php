<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_no', 20)->unique();
            $table->string('full_name', 120);
            $table->string('father_name', 120)->nullable();
            $table->string('mother_name', 120)->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_relation', 60)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->string('guardian_cnic', 20)->nullable();
            $table->string('guardian_address')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->enum('group_stream', ['pre_medical','pre_engineering','computer_science','arts','general'])->default('general');
            $table->string('blood_group', 5)->nullable();
            $table->string('photo')->nullable();
            $table->date('join_date_kort')->nullable();
            $table->boolean('is_orphan')->default(false);
            $table->text('trust_notes')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('class_id');
            $table->index('full_name');
        });
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
