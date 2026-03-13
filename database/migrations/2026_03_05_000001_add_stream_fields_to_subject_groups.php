<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->string('stream', 60)->nullable()->after('group_slug');
            $table->text('description')->nullable()->after('stream');
            $table->boolean('is_active')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('subject_groups', function (Blueprint $table) {
            $table->dropColumn(['stream', 'description', 'is_active']);
        });
    }
};
