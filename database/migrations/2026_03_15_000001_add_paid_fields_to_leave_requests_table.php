<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->boolean('is_paid')->nullable()->after('remarks')->comment('Paid or unpaid leave');
            $table->integer('approved_days')->nullable()->after('is_paid')->comment('Number of days approved (for partial approval)');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'approved_days']);
        });
    }
};
