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
        Schema::table('subject_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_groups', 'min_select')) {
                $table->tinyInteger('min_select')->default(0)->comment('Minimum subjects to select from this group');
            }
            if (!Schema::hasColumn('subject_groups', 'max_select')) {
                $table->tinyInteger('max_select')->default(50)->comment('Maximum subjects to select from this group');
            }
            if (!Schema::hasColumn('subject_groups', 'is_optional_group')) {
                $table->boolean('is_optional_group')->default(false)->comment('Whether this is an optional group (choose one) or compulsory group (all required)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn(['min_select', 'max_select', 'is_optional_group']);
        });
    }
};
