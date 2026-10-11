<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['agenda_number', 'letter_number', 'type', 'letter_date', 'recipient_name', 'recipient_address', 'subject', 'event_date', 'event_time', 'event_place', 'body', 'details', 'notes', 'file_path', 'status', 'verification_note', 'created_by', 'verified_by', 'verified_at', 'kepsek_note', 'kepsek_reviewed_by', 'kepsek_reviewed_at'])]
class OutgoingLetter extends Model
{
    protected function casts(): array
    {
        return ['letter_date' => 'date', 'event_date' => 'date', 'details' => 'array', 'verified_at' => 'datetime', 'kepsek_reviewed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function principalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepsek_reviewed_by');
    }

    public function disposition(): HasOne
    {
        return $this->hasOne(Disposition::class);
    }
}
