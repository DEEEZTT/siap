<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('nis')->unique();
            $table->string('name');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('major');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->date('internship_start')->nullable();
            $table->date('internship_end')->nullable();
            $table->string('predicate')->nullable();
            $table->string('certificate_number')->nullable()->unique();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
