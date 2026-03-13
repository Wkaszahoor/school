<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'optional'
        DB::statement("ALTER TABLE subject_group_subjects MODIFY subject_type ENUM('compulsory', 'major', 'optional') DEFAULT 'compulsory'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE subject_group_subjects MODIFY subject_type ENUM('compulsory', 'major') DEFAULT 'compulsory'");
    }
};
