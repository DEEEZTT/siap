<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('certificate_signer_name', 190)->nullable()->after('completed_at');
            $table->string('certificate_signer_title', 190)->nullable()->after('certificate_signer_name');
            $table->string('certificate_signer_nip', 50)->nullable()->after('certificate_signer_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['certificate_signer_name', 'certificate_signer_title', 'certificate_signer_nip']);
        });
    }
};
