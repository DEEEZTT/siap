<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->string('category', 30)->index();
            $table->string('recipient_name');
            $table->string('recipient_detail')->nullable();
            $table->string('title');
            $table->text('description');
            $table->date('issued_at');
            $table->string('signer_name', 190);
            $table->string('signer_title', 190);
            $table->string('signer_nip', 50)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
