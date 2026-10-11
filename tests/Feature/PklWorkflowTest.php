<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PklWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_humas_can_register_a_student_and_print_a_completion_certificate(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $company = Company::query()->create(['name' => 'Mitra Tanjungpinang', 'status' => 'aktif']);
        $student = Student::query()->create(['nis' => '3684', 'name' => 'Syarifah Nazura', 'major' => 'Rekayasa Perangkat Lunak', 'company_id' => $company->id, 'internship_start' => now()->subMonths(3)->toDateString(), 'internship_end' => now()->subDay()->toDateString()]);
        $this->actingAs($humas)->post(route('students.complete', $student), ['predicate' => 'Sangat Baik'])->assertRedirect(route('certificates.show', $student));
        $student->refresh();
        $this->assertSame('PKL/'.now()->year.'/3684', $student->certificate_number);
        $this->assertNotNull($student->completed_at);
        $this->actingAs($humas)->get(route('certificates.show', $student))->assertOk()->assertSee('Syarifah Nazura')->assertSee('Mitra Tanjungpinang')->assertSee('SANGAT BAIK');
    }

    public function test_humas_cannot_issue_certificate_before_the_internship_ends(): void
    {
        $humas = User::factory()->create(['role' => 'humas']);
        $student = Student::query()->create(['nis' => '4020', 'name' => 'Siswa PKL', 'major' => 'Rekayasa Perangkat Lunak', 'internship_end' => now()->addDay()->toDateString()]);
        $this->actingAs($humas)->post(route('students.complete', $student), ['predicate' => 'Baik'])->assertUnprocessable();
        $this->assertNull($student->fresh()->completed_at);
    }
}
