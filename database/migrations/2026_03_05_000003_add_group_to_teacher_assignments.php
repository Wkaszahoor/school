<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('assignment_type');
            $table->foreign('group_id')->references('id')->on('subject_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
