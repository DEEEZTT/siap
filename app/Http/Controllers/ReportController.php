<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Student;
use App\Services\SpreadsheetExport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    private const AGREEMENT_SORT_COLUMNS = ['name', 'document_number', 'valid_until', 'agreement_status'];

    public const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'sort' => ['nullable', 'in:'.implode(',', self::AGREEMENT_SORT_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        [$year, $month] = $this->period($request);
        $role = $request->user()->role;
        $canSeeLetters = in_array($role, ['tu', 'kepsek', 'kepala_tu'], true);
        $canSeePartnership = in_array($role, ['humas', 'kepala_tu'], true);

        return view('reports.index', [
            'year' => $year,
            'month' => $month,
            'months' => self::MONTHS,
            'years' => range((int) now()->format('Y') + 1, (int) now()->format('Y') - 6),
            'canSeeLetters' => $canSeeLetters,
            'canSeePartnership' => $canSeePartnership,
            'incoming' => $canSeeLetters ? $this->incomingSummary($request, $year, $month) : null,
            'outgoing' => $canSeeLetters ? $this->outgoingSummary($year, $month) : null,
            'certificates' => $canSeePartnership || in_array($role, ['tu'], true) ? $this->certificateSummary($year, $month) : null,
            'agreements' => $canSeePartnership ? $this->agreementSummary(
                $request->string('sort')->toString(),
                $request->input('direction', 'asc'),
            ) : null,
        ]);
    }

    public function export(Request $request, string $section, SpreadsheetExport $spreadsheet): BinaryFileResponse
    {
        [$year, $month] = $this->period($request);
        $role = $request->user()->role;

        abort_unless(in_array($section, ['incoming', 'outgoing', 'agreements', 'certificates'], true), 404);
        abort_if(in_array($section, ['incoming', 'outgoing'], true) && ! in_array($role, ['tu', 'kepsek', 'kepala_tu'], true), 403);
        abort_if($section === 'agreements' && ! in_array($role, ['humas', 'kepala_tu'], true), 403);
        abort_if($section === 'certificates' && ! in_array($role, ['humas', 'tu', 'kepala_tu'], true), 403);

        [$headers, $rows, $sheet] = match ($section) {
            'incoming' => [
                ['No. Agenda', 'Nomor Surat', 'Tanggal Surat', 'Tanggal Diterima', 'Pengirim', 'Perihal', 'Sifat', 'Status'],
                $this->incomingQuery($request, $year, $month)->orderBy('received_at')->get()->map(fn (IncomingLetter $l): array => [
                    $l->agenda_number, $l->letter_number, $l->letter_date->format('d/m/Y'), $l->received_at->format('d/m/Y'),
                    $l->sender, $l->subject, str_replace('_', ' ', $l->classification), str_replace('_', ' ', $l->status),
                ])->all(),
                'Surat Masuk',
            ],
            'outgoing' => [
                ['No. Agenda', 'Nomor Surat', 'Jenis', 'Tanggal Surat', 'Tujuan', 'Perihal', 'Status'],
                $this->outgoingQuery($year, $month)->orderBy('created_at')->get()->map(fn (OutgoingLetter $l): array => [
                    $l->agenda_number ?? '', $l->letter_number ?? '', str_replace('_', ' ', $l->type),
                    $l->letter_date?->format('d/m/Y') ?? '', $l->recipient_name, $l->subject, str_replace('_', ' ', $l->status),
                ])->all(),
                'Surat Keluar',
            ],
            'agreements' => [
                ['Mitra', 'Jenis', 'Nomor Dokumen', 'Ruang Lingkup', 'Berlaku Mulai', 'Berlaku Sampai', 'Status Masa Berlaku', 'Status Kerja Sama'],
                Company::query()->where('partnership_type', 'mou')->orderBy('valid_until')->get()->map(fn (Company $c): array => [
                    $c->name, strtoupper((string) $c->agreement_type), $c->document_number ?? '', $c->scope ?? '',
                    $c->valid_from?->format('d/m/Y') ?? '', $c->valid_until?->format('d/m/Y') ?? '',
                    Company::agreementStatusLabels()[$c->agreementStatus()] ?? '', $c->status === 'aktif' ? 'Aktif' : 'Tidak aktif',
                ])->all(),
                'MoU PKS',
            ],
            'certificates' => [
                ['Nomor Sertifikat', 'Jenis', 'Penerima', 'Keterangan', 'Tanggal Terbit'],
                $this->certificateRows($year, $month),
                'Sertifikat',
            ],
        };

        $path = $spreadsheet->create($headers, $rows, $sheet);

        return response()->download($path, 'laporan-'.$section.'-'.$year.($month ? '-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT) : '').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** @return array{0:int,1:?int} */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        return [(int) ($data['year'] ?? now()->year), isset($data['month']) ? (int) $data['month'] : null];
    }

    private function incomingQuery(Request $request, int $year, ?int $month)
    {
        return IncomingLetter::query()->visibleTo($request->user())
            ->whereYear('received_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('received_at', $month));
    }

    private function outgoingQuery(int $year, ?int $month)
    {
        return OutgoingLetter::query()
            ->whereYear('created_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('created_at', $month));
    }

    private function incomingSummary(Request $request, int $year, ?int $month): array
    {
        $letters = $this->incomingQuery($request, $year, $month)->get(['received_at', 'status', 'classification']);

        return [
            'total' => $letters->count(),
            'byStatus' => $letters->countBy('status')->all(),
            'byClassification' => $letters->countBy('classification')->all(),
            'byMonth' => $this->countByMonth($letters, 'received_at'),
        ];
    }

    private function outgoingSummary(int $year, ?int $month): array
    {
        $letters = $this->outgoingQuery($year, $month)->get(['created_at', 'status', 'type']);

        return [
            'total' => $letters->count(),
            'byStatus' => $letters->countBy('status')->all(),
            'byType' => $letters->countBy('type')->all(),
            'byMonth' => $this->countByMonth($letters, 'created_at'),
        ];
    }

    private function certificateSummary(int $year, ?int $month): array
    {
        $pkl = Student::query()->whereNotNull('completed_at')->whereYear('completed_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('completed_at', $month))->get(['completed_at', 'predicate']);
        $events = Certificate::query()->whereYear('issued_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('issued_at', $month))->get(['issued_at', 'category']);

        $pklMonths = $this->countByMonth($pkl, 'completed_at');
        $eventMonths = $this->countByMonth($events, 'issued_at');

        return [
            'pklTotal' => $pkl->count(),
            'pklByPredicate' => $pkl->countBy('predicate')->all(),
            'eventTotal' => $events->count(),
            'eventByCategory' => $events->countBy('category')->all(),
            'byMonth' => $pklMonths->map(fn (int $count, int $monthNumber): int => $count + $eventMonths[$monthNumber]),
        ];
    }

    private function agreementSummary(string $sort = '', string $direction = 'asc'): array
    {
        $companies = Company::query()->where('partnership_type', 'mou')->orderBy('valid_until')->get();
        $statuses = $companies->map(fn (Company $company): ?string => $company->agreementStatus());
        $attention = $companies->filter(fn (Company $company): bool => in_array($company->agreementStatus(), ['segera_berakhir', 'kedaluwarsa', 'belum_lengkap'], true))->values();

        if (in_array($sort, self::AGREEMENT_SORT_COLUMNS, true)) {
            $attention = $attention->sortBy(
                fn (Company $company) => match ($sort) {
                    'agreement_status' => $company->agreementStatus(),
                    'valid_until' => $company->valid_until?->toDateString(),
                    default => $company->{$sort},
                },
                SORT_NATURAL | SORT_FLAG_CASE,
                $direction === 'desc',
            )->values();
        }

        return [
            'total' => $companies->count(),
            'byStatus' => $statuses->countBy()->all(),
            'attention' => $attention,
            'pklOnly' => Company::query()->where('partnership_type', 'pkl_only')->count(),
        ];
    }

    /** @return Collection<int, int> */
    private function countByMonth(Collection $items, string $dateColumn): Collection
    {
        $counts = array_fill(1, 12, 0);

        foreach ($items as $item) {
            $date = $item->{$dateColumn};

            if ($date) {
                $counts[(int) $date->format('n')]++;
            }
        }

        return collect($counts);
    }

    private function certificateRows(int $year, ?int $month): array
    {
        $rows = [];

        foreach (Student::query()->with('company')->whereNotNull('completed_at')->whereYear('completed_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('completed_at', $month))->orderBy('completed_at')->get() as $student) {
            $rows[] = [$student->certificate_number ?? '', 'PKL', $student->name, ($student->company?->name ?? '').' · '.$student->predicate, $student->completed_at->format('d/m/Y')];
        }

        foreach (Certificate::query()->whereYear('issued_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('issued_at', $month))->orderBy('issued_at')->get() as $certificate) {
            $rows[] = [$certificate->certificate_number, Certificate::CATEGORIES[$certificate->category] ?? $certificate->category, $certificate->recipient_name, $certificate->title, $certificate->issued_at->format('d/m/Y')];
        }

        return $rows;
    }
}
