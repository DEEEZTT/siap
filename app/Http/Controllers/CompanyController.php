<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Student;
use App\Services\SpreadsheetExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CompanyController extends Controller
{
    private const SORTABLE_COLUMNS = ['name', 'partnership_type', 'document_number', 'valid_until', 'contact_person', 'phone', 'students_count', 'status'];

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'in:mou,pkl_only,unclassified'],
            'status' => ['nullable', 'in:aktif,tidak_aktif'],
            'validity' => ['nullable', 'in:berlaku,segera_berakhir,kedaluwarsa,belum_lengkap'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        // Empty select values are normalized to null, so use a truthy fallback.
        $type = $request->input('type') ?: 'all';
        $companies = $this->filteredCompanies($request, $type)
            ->when($request->filled('sort'), fn (Builder $query) => $query
                ->orderBy($request->string('sort')->toString(), $request->input('direction', 'asc'))
                ->orderByDesc('id'), fn (Builder $query) => $query->latest())
            ->paginate(15)
            ->withQueryString();
        $counts = [
            'all' => Company::query()->count(),
            'mou' => Company::query()->where('partnership_type', 'mou')->count(),
            'pkl_only' => Company::query()->where('partnership_type', 'pkl_only')->count(),
            'unclassified' => Company::query()->whereNull('partnership_type')->count(),
        ];

        return view('companies.index', compact('companies', 'counts', 'type'));
    }

    public function export(Request $request, SpreadsheetExport $spreadsheet): BinaryFileResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'in:mou,pkl_only,unclassified'],
            'status' => ['nullable', 'in:aktif,tidak_aktif'],
            'validity' => ['nullable', 'in:berlaku,segera_berakhir,kedaluwarsa,belum_lengkap'],
        ]);
        $type = $data['type'] ?? 'all';
        $companies = $this->filteredCompanies($request, $type)->lazyById(500);
        $rows = (function () use ($companies): \Generator {
            foreach ($companies as $company) {
                yield [
                    $company->name,
                    $company->agreementLabel(),
                    $company->document_number ?? '',
                    $company->scope ?? '',
                    $company->valid_from?->format('d/m/Y') ?? '',
                    $company->valid_until?->format('d/m/Y') ?? '',
                    $company->agreementStatus() ? Company::agreementStatusLabels()[$company->agreementStatus()] : '',
                    $company->status === 'aktif' ? 'Aktif' : 'Tidak aktif',
                    $company->address ?? '',
                    $company->contact_person ?? '',
                    $company->phone ?? '',
                    $company->email ?? '',
                    (string) $company->students_count,
                    $company->mou_document_path ? 'Ada' : 'Tidak ada',
                ];
            }
        })();
        $path = $spreadsheet->create([
            'Nama Perusahaan',
            'Jenis Kemitraan',
            'Nomor Dokumen',
            'Ruang Lingkup',
            'Berlaku Mulai',
            'Berlaku Sampai',
            'Status Masa Berlaku',
            'Status',
            'Alamat',
            'Kontak',
            'Telepon',
            'Email',
            'Jumlah Siswa PKL',
            'Dokumen MOU',
        ], $rows, 'Mitra Perusahaan');

        return response()->download($path, 'data-mitra-perusahaan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $mouDocument = $data['mou_document'] ?? null;
        unset($data['mou_document'], $data['remove_mou_document']);

        if ($mouDocument) {
            $documentPath = $mouDocument->store('company-mous', 'local');

            if ($documentPath === false) {
                return back()->withInput()->withErrors(['mou_document' => 'Dokumen MOU gagal disimpan. Silakan coba lagi.']);
            }

            $data['mou_document_path'] = $documentPath;
        }

        try {
            $company = Company::query()->create($data);
            ActivityLog::record('mitra_dibuat', 'Menambah data mitra '.$company->name.'.', $company);
        } catch (Throwable $exception) {
            if (isset($data['mou_document_path'])) {
                Storage::disk('local')->delete($data['mou_document_path']);
            }

            throw $exception;
        }

        return redirect()->route('companies.index')->with('success', 'Data mitra berhasil disimpan.');
    }

    public function edit(Company $company): View
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request, $company);
        $mouDocument = $data['mou_document'] ?? null;
        $removeMouDocument = (bool) ($data['remove_mou_document'] ?? false);
        $oldDocumentPath = $company->mou_document_path;
        unset($data['mou_document'], $data['remove_mou_document']);

        if ($mouDocument) {
            $documentPath = $mouDocument->store('company-mous', 'local');

            if ($documentPath === false) {
                return back()->withInput()->withErrors(['mou_document' => 'Dokumen MOU gagal disimpan. Silakan coba lagi.']);
            }

            $data['mou_document_path'] = $documentPath;
        } elseif ($removeMouDocument) {
            $data['mou_document_path'] = null;
        }

        try {
            $company->update($data);
            ActivityLog::record('mitra_diubah', 'Mengubah data mitra/dokumen kerja sama '.$company->name.'.', $company);
        } catch (Throwable $exception) {
            if (isset($data['mou_document_path']) && $data['mou_document_path'] !== null && $data['mou_document_path'] !== $oldDocumentPath) {
                Storage::disk('local')->delete($data['mou_document_path']);
            }

            throw $exception;
        }

        if ($oldDocumentPath && ($mouDocument || $removeMouDocument)) {
            Storage::disk('local')->delete($oldDocumentPath);
        }

        return redirect()->route('companies.index')->with('success', 'Data mitra diperbarui.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        abort_if($company->students()->exists(), 409, 'Perusahaan masih dipakai oleh data siswa. Ubah status kerja sama menjadi tidak aktif.');
        $mouDocumentPath = $company->mou_document_path;
        ActivityLog::record('mitra_dihapus', 'Menghapus data mitra '.$company->name.'.', $company);
        $company->delete();

        if ($mouDocumentPath) {
            Storage::disk('local')->delete($mouDocumentPath);
        }

        return redirect()->route('companies.index')->with('success', 'Data mitra dihapus.');
    }

    public function downloadMou(Company $company): StreamedResponse
    {
        abort_unless($company->mou_document_path && Storage::disk('local')->exists($company->mou_document_path), 404);

        return Storage::disk('local')->download(
            $company->mou_document_path,
            Str::slug($company->name).'-mou.pdf',
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    private function validated(Request $request, ?Company $company = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', 'unique:companies,name'.($company ? ','.$company->id : '')],
            'partnership_type' => ['required', 'in:mou,pkl_only'],
            'agreement_type' => ['exclude_unless:partnership_type,mou', 'required', 'in:mou,pks'],
            'document_number' => ['exclude_unless:partnership_type,mou', 'required', 'string', 'max:120'],
            'scope' => ['exclude_unless:partnership_type,mou', 'required', 'string', 'max:2000'],
            'valid_from' => ['exclude_unless:partnership_type,mou', 'required', 'date'],
            'valid_until' => ['exclude_unless:partnership_type,mou', 'required', 'date', 'after_or_equal:valid_from'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contact_person' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'status' => ['required', 'in:aktif,tidak_aktif'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'mou_document' => ['nullable', 'file', 'mimes:pdf', 'max:10240', 'exclude_unless:partnership_type,mou'],
            'remove_mou_document' => ['nullable', 'boolean'],
        ], [
            'agreement_type.required' => 'Pilih jenis dokumen kerja sama (MoU atau PKS).',
            'document_number.required' => 'Nomor dokumen MoU/PKS wajib diisi.',
            'scope.required' => 'Ruang lingkup kerja sama wajib diisi.',
            'valid_from.required' => 'Tanggal mulai berlaku wajib diisi.',
            'valid_until.required' => 'Tanggal berakhir wajib diisi.',
            'valid_until.after_or_equal' => 'Tanggal berakhir tidak boleh lebih awal dari tanggal mulai.',
        ]);

        if ($data['partnership_type'] !== 'mou') {
            $data += ['agreement_type' => null, 'document_number' => null, 'scope' => null, 'valid_from' => null, 'valid_until' => null];
        }

        return $data;
    }

    private function filteredCompanies(Request $request, string $type): Builder
    {
        return Company::query()
            ->withCount('students')
            ->when($request->filled('q'), fn (Builder $query) => $query->where(fn (Builder $search) => $search
                ->where('name', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('address', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('contact_person', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('phone', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('email', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('document_number', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('scope', 'like', '%'.$request->string('q')->toString().'%')))
            ->when($type === 'mou', fn (Builder $query) => $query->where('partnership_type', 'mou'))
            ->when($type === 'pkl_only', fn (Builder $query) => $query->where('partnership_type', 'pkl_only'))
            ->when($type === 'unclassified', fn (Builder $query) => $query->whereNull('partnership_type'))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('validity'), function (Builder $query) use ($request): void {
                $today = now()->toDateString();
                $soon = now()->addDays(Company::EXPIRING_WITHIN_DAYS)->toDateString();
                $query->where('partnership_type', 'mou');

                match ($request->string('validity')->toString()) {
                    'kedaluwarsa' => $query->whereDate('valid_until', '<', $today),
                    'segera_berakhir' => $query->whereDate('valid_until', '>=', $today)->whereDate('valid_until', '<=', $soon),
                    'berlaku' => $query->whereDate('valid_until', '>', $soon),
                    default => $query->where(fn (Builder $incomplete) => $incomplete->whereNull('valid_from')->orWhereNull('valid_until')),
                };
            });
    }
}
