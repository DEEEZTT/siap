<?php

namespace Tests\Feature;

use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfidentialLetterAccessTest extends TestCase
{
    use RefreshDatabase;

    private function letter(User $creator, string $classification = 'rahasia'): IncomingLetter
    {
        return IncomingLetter::query()->create([
            'agenda_number' => 'IM/2026/'.str_pad((string) (IncomingLetter::count() + 1), 4, '0', STR_PAD_LEFT),
            'letter_number' => 'X/'.$classification,
            'letter_date' => now()->toDateString(),
            'received_at' => now()->toDateString(),
            'sender' => 'Dinas Pendidikan',
            'subject' => 'Surat '.$classification,
            'classification' => $classification,
            'status' => 'menunggu_disposisi',
            'created_by' => $creator->id,
        ]);
    }

    public function test_confidential_letter_is_hidden_from_other_tata_usaha_accounts(): void
    {
        $creator = User::factory()->create(['role' => 'tu']);
        $other = User::factory()->create(['role' => 'tu']);
        $letter = $this->letter($creator);
        $this->letter($creator, 'biasa');

        $this->actingAs($other)->get(route('incoming.index'))->assertOk()->assertDontSee('Surat rahasia')->assertSee('Surat biasa');
        $this->actingAs($other)->get(route('incoming.show', $letter))->assertForbidden();
        $this->actingAs($other)->get(route('incoming.disposition.print', $letter))->assertForbidden();
    }

    public function test_confidential_letter_is_visible_to_creator_kepala_tu_and_kepsek(): void
    {
        $creator = User::factory()->create(['role' => 'tu']);
        $letter = $this->letter($creator);

        foreach ([$creator, User::factory()->create(['role' => 'kepala_tu']), User::factory()->create(['role' => 'kepsek'])] as $user) {
            $this->actingAs($user)->get(route('incoming.show', $letter))->assertOk();
        }
    }

    public function test_confidential_attachment_cannot_be_downloaded_by_unauthorised_staff(): void
    {
        Storage::fake('local');
        $creator = User::factory()->create(['role' => 'tu']);
        $other = User::factory()->create(['role' => 'tu']);
        $letter = $this->letter($creator);
        Storage::disk('local')->put('letters/incoming/secret.pdf', 'isi');
        $letter->update(['file_path' => 'letters/incoming/secret.pdf']);

        $this->actingAs($other)->get(route('documents.show', ['type' => 'incoming', 'id' => $letter->id]))->assertForbidden();
        $this->actingAs($creator)->get(route('documents.show', ['type' => 'incoming', 'id' => $letter->id]))->assertOk();
    }
}
