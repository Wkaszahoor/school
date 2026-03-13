<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subject_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 120);
            $table->string('group_slug', 80)->unique();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('subject_groups'); }
};
