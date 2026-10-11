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

    protected function casts(): array
    {
        return ['issued_at' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
