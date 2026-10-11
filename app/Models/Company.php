<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'partnership_type', 'agreement_type', 'document_number', 'scope', 'valid_from', 'valid_until', 'address', 'contact_person', 'phone', 'email', 'status', 'notes', 'mou_document_path'])]
class Company extends Model
{
    public const EXPIRING_WITHIN_DAYS = 90;

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'valid_until' => 'date'];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function isFormalAgreement(): bool
    {
        return $this->partnership_type === 'mou';
    }

    /**
     * Status masa berlaku dokumen MoU/PKS: null jika bukan kemitraan formal.
     * Nilai: belum_lengkap, belum_berlaku, berlaku, segera_berakhir, kedaluwarsa.
     */
    public function agreementStatus(): ?string
    {
        if (! $this->isFormalAgreement()) {
            return null;
        }

        if (! $this->valid_from || ! $this->valid_until) {
            return 'belum_lengkap';
        }

        $today = now()->startOfDay();

        if ($this->valid_until->lt($today)) {
            return 'kedaluwarsa';
        }

        if ($this->valid_from->gt($today)) {
            return 'belum_berlaku';
        }

        return $today->diffInDays($this->valid_until, false) <= self::EXPIRING_WITHIN_DAYS ? 'segera_berakhir' : 'berlaku';
    }

    public static function agreementStatusLabels(): array
    {
        return [
            'belum_lengkap' => 'Data belum lengkap',
            'belum_berlaku' => 'Belum berlaku',
            'berlaku' => 'Berlaku',
            'segera_berakhir' => 'Segera berakhir',
            'kedaluwarsa' => 'Kedaluwarsa',
        ];
    }

    public function agreementLabel(): string
    {
        return match ($this->partnership_type) {
            'mou' => strtoupper($this->agreement_type ?: 'MoU/PKS').' resmi',
            'pkl_only' => 'Mitra PKL saja',
            default => 'Belum diklasifikasikan',
        };
    }
}
