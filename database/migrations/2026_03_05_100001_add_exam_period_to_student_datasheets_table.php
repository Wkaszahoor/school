<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_datesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('student_datesheets', 'exam_period')) {
                $table->string('exam_period', 50)->nullable()->after('total_marks');
            }
            if (!Schema::hasColumn('student_datesheets', 'academic_year')) {
                $table->string('academic_year', 10)->nullable()->after('exam_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_datesheets', function (Blueprint $table) {
            if (Schema::hasColumn('student_datesheets', 'exam_period')) {
                $table->dropColumn('exam_period');
            }
            if (Schema::hasColumn('student_datesheets', 'academic_year')) {
                $table->dropColumn('academic_year');
            }
        });
    }
};
