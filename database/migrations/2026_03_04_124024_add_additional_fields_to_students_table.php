<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Personal identification
            $table->string('student_cnic', 20)->nullable()->after('full_name');
            $table->string('father_cnic', 20)->nullable()->after('father_name');
            $table->string('mother_cnic', 20)->nullable()->after('mother_name');

            // Personal preferences
            $table->string('favorite_color', 50)->nullable()->after('blood_group');
            $table->string('favorite_food', 100)->nullable()->after('favorite_color');
            $table->string('favorite_subject', 100)->nullable()->after('favorite_food');
            $table->text('ambition')->nullable()->after('favorite_subject');

            // Academic details
            $table->string('semester', 20)->nullable()->after('group_stream');

            // Leaving details
            $table->string('reason_left_kort', 255)->nullable()->after('is_active');
            $table->date('leaving_date')->nullable()->after('reason_left_kort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'student_cnic', 'father_cnic', 'mother_cnic',
                'favorite_color', 'favorite_food', 'favorite_subject', 'ambition',
                'semester', 'reason_left_kort', 'leaving_date'
            ]);
        });
    }
};
