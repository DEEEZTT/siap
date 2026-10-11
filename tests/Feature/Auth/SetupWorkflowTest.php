<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_kepala_tu_admin_can_be_created_once(): void
    {
        $this->get('/setup')->assertOk()->assertSee('Buat akun Kepala TU');
        $this->post('/setup', ['name' => 'Kepala TU', 'username' => 'kepala-tu', 'email' => 'admin@smkn4.test', 'password' => 'password-rahasia-123', 'password_confirmation' => 'password-rahasia-123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'admin@smkn4.test', 'role' => 'kepala_tu']);
    }

    public function test_guest_sees_setup_screen_before_first_account(): void
    {
        $this->get('/')->assertOk()->assertSee('Buat akun Kepala TU');
    }
}
