<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->enum('leave_type', ['casual', 'annual', 'emergency', 'other'])->nullable()->after('reason');
            $table->string('other_leave_type', 100)->nullable()->after('leave_type')->comment('Custom leave type when leave_type = "other"');
            $table->string('remarks', 500)->nullable()->after('approved_at')->comment('Principal remarks on approval/rejection');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_type', 'other_leave_type', 'remarks']);
        });
    }
};
