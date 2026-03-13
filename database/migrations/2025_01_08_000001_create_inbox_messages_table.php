<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role', 40);
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_role', 40);
            $table->string('subject', 255);
            $table->text('message_body');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'is_read']);
        });
    }
    public function down(): void { Schema::dropIfExists('inbox_messages'); }
};
