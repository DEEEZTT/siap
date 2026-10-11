<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['agenda_number', 'letter_number', 'letter_date', 'received_at', 'sender', 'subject', 'classification', 'file_path', 'notes', 'status', 'created_by'])]
class IncomingLetter extends Model
{
    protected function casts(): array
    {
        return ['letter_date' => 'date', 'received_at' => 'date'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disposition(): HasOne
    {
        return $this->hasOne(Disposition::class);
    }

    /**
     * Surat berklasifikasi "rahasia" hanya terlihat oleh Kepala TU, Kepala Sekolah,
     * dan Tata Usaha yang mencatat surat tersebut.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->classification !== 'rahasia') {
            return true;
        }

        return in_array($user->role, ['kepala_tu', 'kepsek'], true) || $this->created_by === $user->id;
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($user->role, ['kepala_tu', 'kepsek'], true)) {
            return $query;
        }

        return $query->where(fn (Builder $visible) => $visible
            ->where('classification', '!=', 'rahasia')
            ->orWhere('created_by', $user->id));
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $letter = parent::resolveRouteBinding($value, $field);

        if ($letter && ! $letter->isVisibleTo(auth()->user())) {
            abort(403, 'Surat ini berklasifikasi rahasia dan tidak dapat diakses oleh akun Anda.');
        }

        return $letter;
    }
}
