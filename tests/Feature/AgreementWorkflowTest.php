<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'PT Mitra Kepri',
            'partnership_type' => 'mou',
            'agreement_type' => 'pks',
            'document_number' => '421/PKS/2026',
            'scope' => 'Praktik kerja lapangan dan kunjungan industri.',
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => 'aktif',
        ], $overrides);
    }

    public function test_humas_can_store_a_complete_mou_pks_record(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $this->actingAs($humas)->post(route('companies.store'), $this->payload())->assertRedirect(route('companies.index'));

        $company = Company::query()->firstOrFail();
        $this->assertSame('pks', $company->agreement_type);
        $this->assertSame('421/PKS/2026', $company->document_number);
        $this->assertSame('berlaku', $company->agreementStatus());
        $this->assertDatabaseHas('activity_logs', ['action' => 'mitra_dibuat']);
    }

    public function test_formal_agreement_requires_number_scope_and_dates(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $this->actingAs($humas)->post(route('companies.store'), ['name' => 'PT Kosong', 'partnership_type' => 'mou', 'status' => 'aktif'])
            ->assertSessionHasErrors(['agreement_type', 'document_number', 'scope', 'valid_from', 'valid_until']);
        $this->actingAs($humas)->post(route('companies.store'), $this->payload(['valid_until' => now()->subYear()->toDateString()]))
            ->assertSessionHasErrors(['valid_until']);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_pkl_only_partner_does_not_need_agreement_data_and_clears_old_values(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $company = Company::query()->create($this->payload());
        $this->actingAs($humas)->put(route('companies.update', $company), ['name' => 'PT Mitra Kepri', 'partnership_type' => 'pkl_only', 'status' => 'aktif'])->assertRedirect();

        $company->refresh();
        $this->assertNull($company->document_number);
        $this->assertNull($company->valid_until);
        $this->assertNull($company->agreementStatus());
    }

    public function test_agreement_status_reflects_validity_period(): void
    {
        $make = fn (array $dates): Company => new Company(['partnership_type' => 'mou'] + $dates);

        $this->assertSame('belum_lengkap', $make([])->agreementStatus());
        $this->assertSame('kedaluwarsa', $make(['valid_from' => now()->subYear(), 'valid_until' => now()->subDay()])->agreementStatus());
        $this->assertSame('segera_berakhir', $make(['valid_from' => now()->subYear(), 'valid_until' => now()->addDays(30)])->agreementStatus());
        $this->assertSame('berlaku', $make(['valid_from' => now()->subMonth(), 'valid_until' => now()->addDays(400)])->agreementStatus());
        $this->assertSame('belum_berlaku', $make(['valid_from' => now()->addDays(5), 'valid_until' => now()->addYear()])->agreementStatus());
    }

    public function test_validity_filter_and_dashboard_surface_expiring_agreements(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        Company::query()->create($this->payload(['name' => 'PT Segera', 'valid_until' => now()->addDays(20)->toDateString()]));
        Company::query()->create($this->payload(['name' => 'PT Aman', 'document_number' => '1/AMAN', 'valid_until' => now()->addYear()->toDateString()]));

        $this->actingAs($humas)->get(route('companies.index', ['validity' => 'segera_berakhir']))->assertOk()->assertSee('PT Segera')->assertDontSee('PT Aman');
        $this->actingAs($humas)->get(route('dashboard'))->assertOk()->assertSee('PT Segera');
    }

    public function test_user_account_changes_are_audited_and_only_kepala_tu_can_read_the_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'kepala_tu']);
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pegawai Baru', 'username' => 'pegawai1', 'email' => 'pegawai1@example.test', 'role' => 'tu',
            'position' => 'Staf Administrasi', 'unit' => 'Tata Usaha', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['username' => 'pegawai1', 'position' => 'Staf Administrasi', 'unit' => 'Tata Usaha']);
        $this->assertTrue(ActivityLog::query()->where('action', 'akun_dibuat')->exists());
        $this->actingAs($admin)->get(route('audit.index'))->assertOk()->assertSee('pegawai1');

        $tu = User::factory()->create(['role' => 'tu']);
        $this->actingAs($tu)->get(route('audit.index'))->assertForbidden();
    }
}
