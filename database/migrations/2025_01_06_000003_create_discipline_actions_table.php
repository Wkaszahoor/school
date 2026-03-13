<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('discipline_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipline_id')->constrained('discipline_records')->cascadeOnDelete();
            $table->date('action_date');
            $table->text('action_text');
            $table->foreignId('action_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('discipline_actions'); }
};
