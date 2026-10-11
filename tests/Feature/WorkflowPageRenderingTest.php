<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowPageRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_humas_can_open_student_and_company_forms(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $this->actingAs($humas)->get('/students/create')->assertOk()->assertSee('Tambah siswa PKL');
        $this->actingAs($humas)->get('/companies/create')->assertOk()->assertSee('Tambah perusahaan mitra');
    }

    public function test_kepala_tu_admin_can_open_user_account_form(): void
    {
        $admin = User::factory()->create(['role' => 'kepala_tu']);
        $this->actingAs($admin)->get('/users/create')->assertOk()->assertSee('Buat akun pengguna');
    }

    public function test_kepala_tu_admin_can_review_and_create_outgoing_letters(): void
    {
        $kepalaTu = User::factory()->create(['role' => 'kepala_tu']);
        $this->actingAs($kepalaTu)->get('/outgoing')->assertOk();
        $this->actingAs($kepalaTu)->get('/outgoing/create')->assertOk();
    }
}
