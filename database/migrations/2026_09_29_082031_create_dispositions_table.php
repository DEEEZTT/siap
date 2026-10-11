<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incoming_letter_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('outgoing_letter_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->json('recipients')->nullable();
            $table->json('instructions')->nullable();
            $table->string('other_instruction')->nullable();
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositions');
    }
};
