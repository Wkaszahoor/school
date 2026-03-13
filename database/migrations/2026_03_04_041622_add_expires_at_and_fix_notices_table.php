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
        Schema::table('notices', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_active');
            $table->renameColumn('created_by', 'posted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn('expires_at');
            $table->renameColumn('posted_by', 'created_by');
        });
    }
};
