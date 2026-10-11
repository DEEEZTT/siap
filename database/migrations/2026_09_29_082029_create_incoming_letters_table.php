<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table): void {
            $table->id();
            $table->string('agenda_number')->unique();
            $table->string('letter_number');
            $table->date('letter_date');
            $table->date('received_at');
            $table->string('sender');
            $table->string('subject');
            $table->string('classification')->default('biasa');
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->string('status')->default('menunggu_disposisi');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
