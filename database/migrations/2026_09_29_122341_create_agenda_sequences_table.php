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
        Schema::create('agenda_sequences', function (Blueprint $table): void {
            $table->string('sequence_type', 20);
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->primary(['sequence_type', 'sequence_year']);
        });

        $sequences = [];

        foreach (DB::table('incoming_letters')->select(['agenda_number', 'received_at'])->get() as $letter) {
            if (preg_match('/^IM\/(\d{4})\/(\d+)$/', $letter->agenda_number, $matches) === 1) {
                $key = 'incoming:'.$matches[1];
                $sequences[$key] = [
                    'sequence_type' => 'incoming',
                    'sequence_year' => (int) $matches[1],
                    'last_number' => max($sequences[$key]['last_number'] ?? 0, (int) $matches[2]),
                ];
            }
        }

        foreach (DB::table('outgoing_letters')->whereNotNull('agenda_number')->select('agenda_number')->get() as $letter) {
            if (preg_match('/^SK\/(\d{4})\/(\d+)$/', $letter->agenda_number, $matches) === 1) {
                $key = 'outgoing:'.$matches[1];
                $sequences[$key] = [
                    'sequence_type' => 'outgoing',
                    'sequence_year' => (int) $matches[1],
                    'last_number' => max($sequences[$key]['last_number'] ?? 0, (int) $matches[2]),
                ];
            }
        }

        foreach ($sequences as $sequence) {
            DB::table('agenda_sequences')->insert([...$sequence, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_sequences');
    }
};
