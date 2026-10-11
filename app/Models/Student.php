<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nis', 'academic_year', 'name', 'birth_place', 'birth_date', 'major', 'company_id', 'internship_start', 'internship_end', 'predicate', 'certificate_number', 'completed_at', 'certificate_signer_name', 'certificate_signer_title', 'certificate_signer_nip'])]
class Student extends Model
{
    public const MAJORS = ['RPL', 'GIM', 'PSPT', 'Animasi', 'TKJ', 'DKV'];

    protected static function booted(): void
    {
        static::creating(function (Student $student): void {
            if ($student->academic_year === null) {
                $startYear = now()->month >= 7 ? now()->year : now()->year - 1;
                $student->academic_year = $startYear.'/'.($startYear + 1);
            }
        });
    }

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'internship_start' => 'date', 'internship_end' => 'date', 'completed_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
