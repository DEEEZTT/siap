<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_open_the_report_page_with_its_own_sections(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $humas = User::factory()->create(['role' => 'humas']);
        Company::query()->create(['name' => 'PT Laporan', 'partnership_type' => 'mou', 'status' => 'aktif']);
        IncomingLetter::query()->create([
            'agenda_number' => 'IM/'.now()->year.'/0001', 'letter_number' => 'A/1', 'letter_date' => now()->toDateString(), 'received_at' => now()->toDateString(),
            'sender' => 'Dinas', 'subject' => 'Undangan rapat', 'classification' => 'biasa', 'status' => 'menunggu_disposisi', 'created_by' => $tu->id,
        ]);

        $this->actingAs($tu)->get(route('reports.index'))->assertOk()->assertSee('SURAT MASUK')->assertDontSee('MoU / PKS');
        $this->actingAs($humas)->get(route('reports.index'))->assertOk()->assertSee('MoU / PKS')->assertDontSee('SURAT MASUK');
        $this->actingAs($humas)->get(route('reports.export', 'incoming'))->assertForbidden();
        $this->actingAs($tu)->get(route('reports.export', 'incoming'))->assertOk()->assertDownload();
        $this->actingAs($humas)->get(route('reports.export', 'agreements'))->assertOk()->assertDownload();
    }

    public function test_tata_usaha_can_issue_certificates_for_several_recipients_with_sequential_numbers(): void
    {
        $tu = User::factory()->create(['role' => 'tu']);
        $this->actingAs($tu)->post(route('event-certificates.store'), [
            'category' => 'penghargaan',
            'title' => 'Sertifikat Penghargaan',
            'description' => 'Sebagai Juara 1 Lomba Kompetensi Siswa.',
            'issued_at' => '2026-09-15',
            'signer_name' => 'Pembina OSIS',
            'signer_title' => 'Pembina',
            'recipients' => "Ahmad Fauzi | XII RPL 3\nSiti Aisyah\nAhmad Fauzi | XII RPL 3",
        ])->assertRedirect(route('event-certificates.index'));

        $this->assertDatabaseCount('certificates', 2);
        $this->assertSame(['SRT/2026/0001', 'SRT/2026/0002'], Certificate::query()->orderBy('id')->pluck('certificate_number')->all());
        $first = Certificate::query()->orderBy('id')->firstOrFail();
        $this->assertSame('XII RPL 3', $first->recipient_detail);
        $this->actingAs($tu)->get(route('event-certificates.show', $first))->assertOk()->assertSee('AHMAD FAUZI')->assertSee('SRT/2026/0001');
        $this->actingAs($tu)->get(route('event-certificates.print_all'))->assertOk()->assertSee('SITI AISYAH');
        $this->assertDatabaseHas('activity_logs', ['action' => 'sertifikat_kegiatan_terbit']);
    }

    public function test_kepsek_cannot_issue_event_certificates_and_only_kepala_tu_can_delete(): void
    {
        $kepsek = User::factory()->create(['role' => 'kepsek']);
        $this->actingAs($kepsek)->get(route('event-certificates.index'))->assertForbidden();

        $humas = User::factory()->create(['role' => 'humas']);
        $certificate = Certificate::query()->create([
            'certificate_number' => 'SRT/2026/0001', 'category' => 'peserta', 'recipient_name' => 'Budi', 'title' => 'Sertifikat', 'description' => 'Peserta workshop',
            'issued_at' => now()->toDateString(), 'signer_name' => 'A', 'signer_title' => 'B', 'created_by' => $humas->id,
        ]);
        $this->actingAs($humas)->delete(route('event-certificates.destroy', $certificate))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'kepala_tu']))->delete(route('event-certificates.destroy', $certificate))->assertRedirect();
        $this->assertDatabaseCount('certificates', 0);
    }
}
