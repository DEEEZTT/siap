<?php

namespace Tests\Feature;

use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutgoingLetterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tu_to_kepala_tu_verification_flow_assigns_agenda_number(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $this->actingAs($tu)->get('/outgoing/create')->assertOk()->assertSee('Pilih jenis surat')->assertSee('Surat undangan');
        $this->actingAs($tu)->get(route('outgoing.create.type', 'undangan'))->assertOk()->assertSee('Buat Surat undangan');
        $this->actingAs($tu)->post('/outgoing', ['type' => 'undangan', 'details' => ['invitation_context' => 'rapat wali murid', 'grade_class' => 'X'], 'event_date' => '2026-10-10', 'event_time' => '09.00 WIB', 'event_place' => 'Aula sekolah', 'notes' => 'Agenda rapat', 'action' => 'submit'])->assertRedirect();
        $outgoing = OutgoingLetter::query()->firstOrFail();
        $this->assertSame('menunggu_verifikasi', $outgoing->status);
        $kepalaTu = User::factory()->create(['role' => 'kepala_tu']);
        $this->actingAs($kepalaTu)->post(route('outgoing.verify', $outgoing), ['decision' => 'setuju'])->assertRedirect();
        $outgoing->refresh();
        $this->assertSame('menunggu_persetujuan_kepsek', $outgoing->status);
        $this->assertNotNull($outgoing->agenda_number);
        $this->assertNotNull($outgoing->letter_number);
        $this->actingAs($kepalaTu)->get(route('outgoing.show', $outgoing))->assertOk()->assertSee('Menunggu keputusan Kepala Sekolah.');
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $this->actingAs($kepsek)->post(route('outgoing.principal_review', $outgoing), ['decision' => 'setuju'])->assertRedirect();
        $this->assertSame('siap_dicetak', $outgoing->fresh()->status);
        $this->actingAs($tu)->get(route('outgoing.print', $outgoing))->assertOk()->assertSee('PEMERINTAH PROVINSI KEPULAUAN RIAU');
    }

    public function test_tu_cannot_verify_an_outgoing_letter(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $letter = OutgoingLetter::query()->create(['recipient_name' => 'Wali Murid', 'subject' => 'Undangan', 'event_date' => '2026-10-10', 'event_time' => '09.00', 'event_place' => 'Aula', 'status' => 'menunggu_verifikasi', 'created_by' => $tu->id]);
        $this->actingAs($tu)->post(route('outgoing.verify', $letter), ['decision' => 'setuju'])->assertForbidden();
    }

    public function test_verification_rejects_revision_without_a_note(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $kepalaTu = User::factory()->create(['role' => 'kepala_tu']);
        $letter = OutgoingLetter::query()->create(['recipient_name' => 'Wali Murid', 'subject' => 'Undangan', 'event_date' => '2026-10-10', 'event_time' => '09.00', 'event_place' => 'Aula', 'status' => 'menunggu_verifikasi', 'created_by' => $tu->id]);
        $this->actingAs($kepalaTu)->post(route('outgoing.verify', $letter), ['decision' => 'revisi'])->assertSessionHasErrors('verification_note');
        $this->assertSame('menunggu_verifikasi', $letter->fresh()->status);
    }
}
