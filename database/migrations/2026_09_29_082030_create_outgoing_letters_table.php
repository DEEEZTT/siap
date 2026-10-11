<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letters', function (Blueprint $table): void {
            $table->id();
            $table->string('agenda_number')->nullable()->unique();
            $table->string('letter_number')->nullable();
            $table->string('type')->default('undangan');
            $table->date('letter_date')->nullable();
            $table->string('recipient_name');
            $table->text('recipient_address')->nullable();
            $table->string('subject');
            $table->date('event_date')->nullable();
            $table->string('event_time')->nullable();
            $table->string('event_place')->nullable();
            $table->longText('body')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('draft');
            $table->text('verification_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
