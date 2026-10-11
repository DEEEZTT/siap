<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dispositions', function (Blueprint $table): void {
            $table->string('other_recipient', 190)->nullable()->after('recipients');
            $table->foreignId('confirmed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->text('confirmation_note')->nullable()->after('confirmed_at');
            $table->json('revision_history')->nullable()->after('confirmed_at');
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->text('kepsek_note')->nullable()->after('verification_note');
            $table->foreignId('kepsek_reviewed_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('kepsek_reviewed_at')->nullable()->after('kepsek_reviewed_by');
            $table->index('status');
        });

        DB::table('outgoing_letters')
            ->where('status', 'menunggu_disposisi')
            ->update(['status' => 'menunggu_persetujuan_kepsek']);

        DB::table('incoming_letters')
            ->where('status', 'selesai')
            ->whereIn('id', DB::table('dispositions')->select('incoming_letter_id')->whereNotNull('incoming_letter_id'))
            ->update(['status' => 'menunggu_konfirmasi_tu']);

        Schema::table('incoming_letters', function (Blueprint $table): void {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('outgoing_letters')
            ->where('status', 'menunggu_persetujuan_kepsek')
            ->update(['status' => 'menunggu_disposisi']);

        DB::table('incoming_letters')
            ->where('status', 'menunggu_konfirmasi_tu')
            ->update(['status' => 'selesai']);

        Schema::table('incoming_letters', function (Blueprint $table): void {
            $table->dropIndex(['status']);
        });

        Schema::table('outgoing_letters', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('kepsek_reviewed_by');
            $table->dropColumn(['kepsek_note', 'kepsek_reviewed_at']);
        });

        Schema::table('dispositions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['other_recipient', 'confirmed_at', 'confirmation_note', 'revision_history']);
        });
    }
};
