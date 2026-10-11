<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const OVERDUE_AFTER_DAYS = 3;

    public function __invoke(): View
    {
        $user = request()->user();
        $role = $user->role;
        $canSeeIncoming = in_array($role, ['tu', 'kepsek', 'kepala_tu'], true);
        $canSeeOutgoing = in_array($role, ['tu', 'kepala_tu', 'kepsek'], true);
        $canSeePkl = in_array($role, ['humas', 'kepala_tu'], true);
        $incomingActionStatuses = match ($role) {
            'kepsek' => ['menunggu_disposisi'],
            'tu' => ['menunggu_konfirmasi_tu'],
            'kepala_tu' => ['menunggu_disposisi', 'menunggu_konfirmasi_tu'],
            default => [],
        };
        $outgoingActionStatuses = match ($role) {
            'kepsek' => ['menunggu_persetujuan_kepsek'],
            'tu' => ['perlu_revisi', 'perlu_revisi_kepsek'],
            'kepala_tu' => ['menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek'],
            default => [],
        };
        $overdueDate = now()->subDays(self::OVERDUE_AFTER_DAYS)->toDateString();
        $incomingOverdue = match ($role) {
            'kepsek' => IncomingLetter::visibleTo($user)->where('status', 'menunggu_disposisi')->whereDate('received_at', '<=', $overdueDate)->count(),
            'tu' => IncomingLetter::visibleTo($user)->where('status', 'menunggu_konfirmasi_tu')
                ->whereHas('disposition', fn (Builder $query) => $query->whereDate('completed_at', '<=', $overdueDate))
                ->count(),
            'kepala_tu' => IncomingLetter::visibleTo($user)->where(function (Builder $query) use ($overdueDate): void {
                $query->where(fn (Builder $pending) => $pending->where('status', 'menunggu_disposisi')->whereDate('received_at', '<=', $overdueDate))
                    ->orWhere(fn (Builder $confirmation) => $confirmation->where('status', 'menunggu_konfirmasi_tu')
                        ->whereHas('disposition', fn (Builder $disposition) => $disposition->whereDate('completed_at', '<=', $overdueDate)));
            })->count(),
            default => 0,
        };
        $outgoingOverdueQuery = OutgoingLetter::whereIn('status', $outgoingActionStatuses);
        $outgoingOverdue = match ($role) {
            'kepsek' => $outgoingOverdueQuery->whereDate('verified_at', '<=', $overdueDate)->count(),
            'tu', 'kepala_tu' => $outgoingOverdueQuery->whereDate('updated_at', '<=', $overdueDate)->count(),
            default => 0,
        };

        return view('dashboard', [
            'incomingCount' => $canSeeIncoming ? IncomingLetter::visibleTo($user)->count() : 0,
            'incomingPending' => $incomingActionStatuses ? IncomingLetter::visibleTo($user)->whereIn('status', $incomingActionStatuses)->count() : 0,
            'incomingOverdue' => $incomingOverdue,
            'outgoingCount' => $canSeeOutgoing ? OutgoingLetter::count() : 0,
            'outgoingPending' => $outgoingActionStatuses ? OutgoingLetter::whereIn('status', $outgoingActionStatuses)->count() : 0,
            'outgoingOverdue' => $outgoingOverdue,
            'studentCount' => $canSeePkl ? Student::count() : 0,
            'certificateCount' => $canSeePkl ? Student::whereNotNull('completed_at')->count() : 0,
            'companyCount' => $canSeePkl ? Company::count() : 0,
            'canSeeIncoming' => $canSeeIncoming,
            'canSeeOutgoing' => $canSeeOutgoing,
            'canSeePkl' => $canSeePkl,
            'incomingActionLabel' => match ($role) {
                'kepsek' => 'menunggu disposisi Anda',
                'tu' => 'menunggu konfirmasi tindak lanjut',
                default => 'perlu tindakan surat masuk',
            },
            'outgoingActionLabel' => match ($role) {
                'kepsek' => 'menunggu persetujuan Anda',
                'tu' => 'perlu diperbaiki Tata Usaha',
                default => 'perlu tindakan surat keluar',
            },
            'recentIncoming' => $canSeeIncoming
                ? IncomingLetter::query()
                    ->visibleTo($user)
                    ->with('disposition')
                    ->when($incomingActionStatuses, fn (Builder $query) => $query->whereIn('status', $incomingActionStatuses))
                    ->latest('received_at')
                    ->limit(5)
                    ->get()
                : collect(),
            'recentOutgoing' => $canSeeOutgoing
                ? OutgoingLetter::query()
                    ->when($outgoingActionStatuses, fn (Builder $query) => $query->whereIn('status', $outgoingActionStatuses))
                    ->latest()
                    ->limit(5)
                    ->get()
                : collect(),
            'agreementsExpiring' => $canSeePkl
                ? Company::query()->where('partnership_type', 'mou')->where('status', 'aktif')
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<=', now()->addDays(Company::EXPIRING_WITHIN_DAYS)->toDateString())
                    ->orderBy('valid_until')->limit(5)->get()
                : collect(),
            'agreementsExpiringCount' => $canSeePkl
                ? Company::query()->where('partnership_type', 'mou')->where('status', 'aktif')
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<=', now()->addDays(Company::EXPIRING_WITHIN_DAYS)->toDateString())->count()
                : 0,
            'recentStudents' => $canSeePkl ? Student::with('company')->latest()->limit(5)->get() : collect(),
        ]);
    }
}
