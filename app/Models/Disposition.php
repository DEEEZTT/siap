<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['incoming_letter_id', 'outgoing_letter_id', 'recipients', 'other_recipient', 'instructions', 'other_instruction', 'note', 'attachment_path', 'completed_by', 'completed_at', 'confirmed_by', 'confirmed_at', 'confirmation_note', 'revision_history'])]
class Disposition extends Model
{
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'instructions' => 'array',
            'completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'revision_history' => 'array',
        ];
    }

    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class);
    }

    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
