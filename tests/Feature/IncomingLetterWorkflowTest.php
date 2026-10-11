<?php

namespace Tests\Feature;

use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncomingLetterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tu_can_record_an_incoming_letter_with_a_private_attachment(): void
    {
        Storage::fake('local');
        $tu = User::factory()->create(['role' => 'tu']);
        $this->actingAs($tu)->post('/incoming', ['letter_number' => 'B/12/2026', 'letter_date' => '2026-09-28', 'received_at' => '2026-09-29', 'sender' => 'Dinas Pendidikan', 'subject' => 'Undangan rapat', 'classification' => 'segera', 'file' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf'), 'notes' => 'Segera diteruskan'])->assertRedirect('/incoming');
        $letter = IncomingLetter::query()->firstOrFail();
        $this->assertSame('IM/2026/0001', $letter->agenda_number);
        $this->assertSame('menunggu_disposisi', $letter->status);
        Storage::disk('local')->assertExists($letter->file_path);
        $this->actingAs($tu)->get(route('incoming.show', $letter))->assertOk()->assertSee('Undangan rapat');
    }

    public function test_humas_cannot_open_the_incoming_letter_module(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $this->actingAs($humas)->get('/incoming/create')->assertForbidden();
    }

    public function test_kepsek_can_save_disposition_and_finish_the_incoming_letter(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $letter = IncomingLetter::query()->create(['agenda_number' => 'IM/2026/0001', 'letter_number' => 'B/12/2026', 'letter_date' => '2026-09-28', 'received_at' => '2026-09-29', 'sender' => 'Dinas Pendidikan', 'subject' => 'Undangan rapat', 'classification' => 'biasa', 'file_path' => 'letters/incoming/sample.pdf', 'status' => 'menunggu_disposisi', 'created_by' => $tu->id]);
        $this->actingAs($kepsek)->post(route('incoming.disposition', $letter), ['recipients' => ['Waka Humas'], 'instruction' => 'Koordinasi/konfirmasi', 'note' => 'Tindak lanjuti'])->assertRedirect();
        $this->assertSame('menunggu_konfirmasi_tu', $letter->fresh()->status);
        $this->actingAs($tu)->post(route('incoming.follow_up.confirm', $letter), ['confirmation_note' => 'Tindak lanjut sudah dikonfirmasi'])->assertRedirect();
        $this->assertSame('selesai', $letter->fresh()->status);
        $this->assertDatabaseHas('dispositions', ['incoming_letter_id' => $letter->id, 'completed_by' => $kepsek->id, 'confirmed_by' => $tu->id, 'confirmation_note' => 'Tindak lanjut sudah dikonfirmasi', 'note' => 'Tindak lanjuti']);
        $this->actingAs($kepsek)->get(route('incoming.disposition.print', $letter))->assertOk()->assertSee('LEMBAR DISPOSISI')->assertSee('Tindak lanjuti');
    }
}
