<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('resource_key', 80);
            $table->string('action_key', 40);
            $table->string('ability_key', 120)->unique()->comment('resource.action');
            $table->string('permission_name', 190);
            $table->timestamps();
            $table->unique(['resource_key', 'action_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('permissions'); }
};
