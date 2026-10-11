<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('agreement_type', 10)->nullable()->after('partnership_type');
            $table->string('document_number', 120)->nullable()->after('agreement_type')->index();
            $table->text('scope')->nullable()->after('document_number');
            $table->date('valid_from')->nullable()->after('scope');
            $table->date('valid_until')->nullable()->after('valid_from')->index();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex(['document_number']);
            $table->dropIndex(['valid_until']);
            $table->dropColumn(['agreement_type', 'document_number', 'scope', 'valid_from', 'valid_until']);
        });
    }
};
