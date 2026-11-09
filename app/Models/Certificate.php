<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['certificate_number', 'category', 'recipient_name', 'recipient_detail', 'title', 'description', 'issued_at', 'signer_name', 'signer_title', 'signer_nip', 'created_by'])]
class Certificate extends Model
{
    public const CATEGORIES = [
        'penghargaan' => 'Penghargaan / prestasi',
        'peserta' => 'Peserta kegiatan',
        'narasumber' => 'Narasumber / pembimbing',
        'lainnya' => 'Lainnya',
    ];

    public const DEFAULT_TITLES = [
        'penghargaan' => 'Sertifikat Penghargaan',
        'peserta' => 'Sertifikat Peserta Kegiatan',
        'narasumber' => 'Sertifikat Narasumber',
        'lainnya' => 'Sertifikat Kegiatan',
    ];

    public function displayTitle(): string
    {
        $title = trim((string) $this->title);

        if (in_array($title, array_values(self::DEFAULT_TITLES), true)) {
            return self::DEFAULT_TITLES[$this->category] ?? $title;
        }

        return $title;
    }

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
