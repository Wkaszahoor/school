<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_group_id')->nullable()->after('class_id');
            $table->foreign('subject_group_id')->references('id')->on('subject_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['subject_group_id']);
            $table->dropColumn('subject_group_id');
        });
    }
};
