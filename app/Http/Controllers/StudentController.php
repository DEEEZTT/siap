<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Student;
use App\Services\SpreadsheetExport;
use App\Services\StudentSpreadsheet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class StudentController extends Controller
{
    private const SORTABLE_COLUMNS = ['name', 'nis', 'company', 'internship_start', 'predicate', 'completed_at'];

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'academic_year' => ['nullable', 'regex:/^\d{4}\/\d{4}$/'],
            'status' => ['nullable', 'in:berjalan,selesai'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        $academicYears = $this->academicYears();
        $selectedAcademicYear = $request->input('academic_year', $this->currentAcademicYear());
        $students = $this->filteredStudents($request, $selectedAcademicYear)
            ->when($request->filled('sort'), function (Builder $query) use ($request): void {
                $sort = $request->string('sort')->toString();
                $direction = $request->input('direction', 'asc');
                if ($sort === 'company') {
                    $query->orderBy(Company::query()->select('name')->whereColumn('companies.id', 'students.company_id')->limit(1), $direction);
                } else {
                    $query->orderBy($sort, $direction);
                }
                $query->orderByDesc('id');
            }, fn (Builder $query) => $query->latest('created_at'))
            ->paginate(15)
            ->withQueryString();
        $totalStudentCount = Student::query()->count();
        $issuedCertificateCount = Student::query()->whereNotNull('completed_at')->count();

        return view('students.index', compact('students', 'academicYears', 'selectedAcademicYear', 'totalStudentCount', 'issuedCertificateCount'));
    }

    public function export(Request $request, SpreadsheetExport $spreadsheet, StudentSpreadsheet $studentSpreadsheet): BinaryFileResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'academic_year' => ['nullable', 'regex:/^\d{4}\/\d{4}$/'],
            'status' => ['nullable', 'in:berjalan,selesai'],
        ]);
        $selectedAcademicYear = $request->input('academic_year', $this->currentAcademicYear());
        $students = $this->filteredStudents($request, $selectedAcademicYear)->lazyById(500);
        $rows = (function () use ($students): \Generator {
            foreach ($students as $student) {
                yield [
                    $student->nis,
                    $student->name,
                    $student->birth_place ?? '',
                    $student->birth_date?->format('d/m/Y') ?? '',
                    $student->major,
                    $student->company?->name ?? '',
                    $student->internship_start?->format('d/m/Y') ?? '',
                    $student->internship_end?->format('d/m/Y') ?? '',
                    $student->predicate ?? '',
                    $student->completed_at ? 'Selesai' : 'Berlangsung',
                    $student->certificate_number ?? '',
                    $student->completed_at?->format('d/m/Y') ?? '',
                    $student->academic_year,
                ];
            }
        })();
        $path = $spreadsheet->create([
            ...$studentSpreadsheet->headers(),
            'Status PKL',
            'Nomor Sertifikat',
            'Tanggal Sertifikat',
            'Tahun Pelajaran',
        ], $rows, 'Siswa PKL');
        $yearName = $selectedAcademicYear ? str_replace('/', '-', $selectedAcademicYear) : 'semua-tahun';

        return response()->download($path, 'data-siswa-pkl-'.$yearName.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function create(): View
    {
        return view('students.create', [
            'companies' => Company::query()->orderBy('name')->get(),
            'academicYears' => $this->academicYears(),
            'currentAcademicYear' => $this->currentAcademicYear(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Student::query()->create($this->validatedStudent($request));

        return redirect()->route('students.index')->with('success', 'Data siswa PKL disimpan.');
    }

    public function edit(Student $student): View
    {
        abort_if($student->completed_at, 409, 'Data yang sertifikatnya telah terbit tidak dapat diubah.');

        return view('students.edit', [
            'student' => $student,
            'companies' => Company::query()->orderBy('name')->get(),
            'academicYears' => $this->academicYears(),
            'currentAcademicYear' => $this->currentAcademicYear(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        abort_if($student->completed_at, 409, 'Data yang sertifikatnya telah terbit tidak dapat diubah.');
        $student->update($this->validatedStudent($request, $student));

        return redirect()->route('students.index', ['academic_year' => $student->academic_year])->with('success', 'Data siswa diperbarui.');
    }

    public function import(Request $request, StudentSpreadsheet $spreadsheet): RedirectResponse
    {
        $data = $request->validate([
            'academic_year' => ['required', Rule::in($this->academicYears())],
            'file' => ['required', 'file', 'extensions:xlsx,csv', 'max:10240'],
        ]);
        $rows = $spreadsheet->read($data['file']);
        $headers = $this->headerColumns($rows[0]['cells'] ?? []);
        $companyIds = Company::query()->get(['id', 'name'])
            ->mapWithKeys(fn (Company $company): array => [mb_strtolower(trim($company->name)) => $company->id])
            ->all();
        $preparedRows = [];
        $errors = [];
        $seenNis = [];

        foreach (array_slice($rows, 1) as $row) {
            $cells = $row['cells'];

            if (collect($cells)->every(fn (string $value): bool => trim($value) === '')) {
                continue;
            }

            $value = fn (string $field): string => trim((string) ($cells[$headers[$field] ?? -1] ?? ''));
            $nis = $value('nis');
            $companyName = $value('company');
            $birthDate = $this->dateValue($value('birth_date'));
            $internshipStart = $this->dateValue($value('internship_start'));
            $internshipEnd = $this->dateValue($value('internship_end'));

            $studentData = [
                'nis' => $nis,
                'name' => $value('name'),
                'birth_place' => $value('birth_place') ?: null,
                'birth_date' => $birthDate,
                'major' => $value('major'),
                'company_id' => $companyName === '' ? null : ($companyIds[mb_strtolower($companyName)] ?? null),
                'internship_start' => $internshipStart,
                'internship_end' => $internshipEnd,
                'predicate' => $value('predicate') ?: null,
                'academic_year' => $data['academic_year'],
            ];

            $rowValidator = Validator::make($studentData, [
                'nis' => ['required', 'string', 'max:40'],
                'name' => ['required', 'string', 'max:190'],
                'birth_place' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date'],
                'major' => ['required', Rule::in(Student::MAJORS)],
                'internship_start' => ['nullable', 'date'],
                'internship_end' => ['nullable', 'date', 'after_or_equal:internship_start'],
                'predicate' => ['nullable', Rule::in(['Cukup', 'Baik', 'Sangat Baik'])],
            ]);

            if ($rowValidator->fails()) {
                foreach ($rowValidator->errors()->all() as $message) {
                    $errors[] = 'Baris '.$row['row'].': '.$message;
                }
            }

            if ($nis !== '') {
                $normalizedNis = mb_strtolower($nis);

                if (isset($seenNis[$normalizedNis])) {
                    $errors[] = 'Baris '.$row['row'].': NIS '.$nis.' tercantum lebih dari sekali pada berkas.';
                }

                $seenNis[$normalizedNis] = true;
            }

            $preparedRows[] = [
                ...$studentData,
                '_source_row' => $row['row'],
                '_company_name' => $companyName !== '' && $studentData['company_id'] === null ? $companyName : null,
            ];

            if (count($errors) >= 20) {
                break;
            }
        }

        if ($preparedRows === [] && $errors === []) {
            $errors[] = 'Tidak ada baris data untuk diimpor.';
        }

        if ($seenNis !== []) {
            $existingNis = Student::query()
                ->where('academic_year', $data['academic_year'])
                ->whereIn('nis', array_column($preparedRows, 'nis'))
                ->pluck('nis')
                ->map(fn (string $nis): string => mb_strtolower($nis))
                ->all();
            $existingNis = array_flip($existingNis);

            foreach ($preparedRows as $index => $studentData) {
                if (isset($existingNis[mb_strtolower($studentData['nis'])])) {
                    $errors[] = 'Baris '.$studentData['_source_row'].': NIS '.$studentData['nis'].' sudah terdaftar pada tahun pelajaran '.$data['academic_year'].'.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 20)]);
        }

        [$preparedRows, $newCompanyCount] = DB::transaction(function () use ($preparedRows): array {
            $timestamp = now();
            $newCompanyCount = 0;

            foreach ($preparedRows as $index => $studentData) {
                if ($studentData['_company_name'] !== null) {
                    $company = Company::query()->firstOrCreate(
                        ['name' => $studentData['_company_name']],
                        ['partnership_type' => 'pkl_only', 'status' => 'aktif'],
                    );
                    $studentData['company_id'] = $company->id;
                    $newCompanyCount += $company->wasRecentlyCreated ? 1 : 0;
                }

                unset($studentData['_source_row'], $studentData['_company_name']);
                $preparedRows[$index] = [
                    ...$studentData,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Student::query()->insert($preparedRows);

            return [$preparedRows, $newCompanyCount];
        });

        $companyMessage = $newCompanyCount > 0
            ? ' '.$newCompanyCount.' perusahaan baru otomatis ditambahkan sebagai Mitra PKL saja.'
            : '';

        return redirect()->route('students.index', ['academic_year' => $data['academic_year']])
            ->with('success', count($preparedRows).' data siswa berhasil diimpor untuk tahun pelajaran '.$data['academic_year'].'.'.$companyMessage);
    }

    public function template(StudentSpreadsheet $spreadsheet): BinaryFileResponse
    {
        $path = $spreadsheet->createTemplate();

        return response()->download($path, 'template-data-siswa-pkl.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function destroy(Student $student): RedirectResponse
    {
        ActivityLog::record('siswa_dihapus', 'Menghapus data siswa PKL '.$student->name.' (NIS '.$student->nis.')'.($student->completed_at ? ' beserta sertifikatnya.' : '.'), $student);
        $student->delete();

        return redirect()->route('students.index', ['academic_year' => $student->academic_year])->with('success', 'Data siswa dihapus.');
    }

    public function complete(Request $request, Student $student): RedirectResponse
    {
        abort_if($student->completed_at, 409, 'Sertifikat siswa sudah diterbitkan.');
        abort_if(! $student->internship_end || $student->internship_end->isFuture(), 422, 'PKL siswa belum selesai.');
        $data = $request->validate(['predicate' => ['required', Rule::in(['Cukup', 'Baik', 'Sangat Baik'])]]);
        $student->update(['predicate' => $data['predicate'], 'completed_at' => now(), 'certificate_number' => $student->certificate_number ?? 'PKL/'.now()->year.'/'.$student->nis]);

        ActivityLog::record('sertifikat_pkl_terbit', 'Menerbitkan sertifikat PKL '.$student->certificate_number.' untuk '.$student->name.'.', $student);

        return redirect()->route('certificates.show', $student)->with('success', 'Sertifikat siap dicetak.');
    }

    /** @return array<string, int> */
    private function headerColumns(array $cells): array
    {
        $aliases = [
            'nis' => ['nis', 'nomorinduksiswa', 'nomorinduk'],
            'name' => ['namalengkap', 'namasiswa', 'nama'],
            'birth_place' => ['tempatlahir'],
            'birth_date' => ['tanggallahir'],
            'major' => ['konsentrasikeahlian', 'jurusan', 'programkeahlian'],
            'company' => ['perusahaanpkl', 'perusahaan', 'mitra'],
            'internship_start' => ['mulaipkl', 'tanggalmulaipkl'],
            'internship_end' => ['selesaipkl', 'tanggalselesaipkl'],
            'predicate' => ['predikat'],
        ];
        $columns = [];

        foreach ($cells as $index => $header) {
            $normalizedHeader = preg_replace('/[^a-z0-9]/', '', mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim((string) $header)) ?: ''));

            foreach ($aliases as $field => $fieldAliases) {
                if (in_array($normalizedHeader, $fieldAliases, true)) {
                    if (isset($columns[$field])) {
                        throw ValidationException::withMessages(['file' => 'Header kolom "'.$header.'" muncul lebih dari sekali.']);
                    }

                    $columns[$field] = $index;
                }
            }
        }

        $missingHeaders = array_diff(['nis', 'name', 'major'], array_keys($columns));

        if ($missingHeaders !== []) {
            throw ValidationException::withMessages(['file' => 'Header wajib tidak ditemukan. Gunakan template Excel SIAP: NIS, Nama Lengkap, dan Konsentrasi Keahlian.']);
        }

        return $columns;
    }

    private function dateValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 2958466) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString();
        }

        foreach (['!d/m/Y', '!d-m-Y', '!Y-m-d', '!m/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false && $date->format(substr($format, 1)) === $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $value;
    }

    private function validatedStudent(Request $request, ?Student $student = null): array
    {
        $academicYear = $request->input('academic_year');
        $uniqueNis = Rule::unique('students', 'nis')->where('academic_year', $academicYear);

        if ($student !== null) {
            $uniqueNis->ignore($student->id);
        }

        return $request->validate([
            'academic_year' => ['required', Rule::in($this->academicYears())],
            'nis' => [
                'required',
                'string',
                'max:40',
                $uniqueNis,
            ],
            'name' => ['required', 'string', 'max:190'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'major' => ['required', Rule::in(array_unique([...Student::MAJORS, ...($student ? [$student->major] : [])]))],
            'company_id' => ['nullable', 'exists:companies,id'],
            'internship_start' => ['nullable', 'date'],
            'internship_end' => ['nullable', 'date', 'after_or_equal:internship_start'],
            'predicate' => ['nullable', Rule::in(['Cukup', 'Baik', 'Sangat Baik'])],
        ]);
    }

    private function filteredStudents(Request $request, ?string $academicYear): Builder
    {
        return Student::query()
            ->with('company')
            ->when($request->filled('q'), fn (Builder $query) => $query->where(fn (Builder $nestedQuery) => $nestedQuery
                ->where('name', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('nis', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('major', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhereHas('company', fn (Builder $companyQuery) => $companyQuery->where('name', 'like', '%'.$request->string('q')->toString().'%'))))
            ->when($request->filled('status'), fn (Builder $query) => $request->string('status')->toString() === 'selesai'
                ? $query->whereNotNull('completed_at')
                : $query->whereNull('completed_at'))
            ->when($academicYear, fn (Builder $query) => $query->where('academic_year', $academicYear));
    }

    /** @return list<string> */
    private function academicYears(): array
    {
        $currentStart = (int) substr($this->currentAcademicYear(), 0, 4);
        $years = Student::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year')->all();

        foreach (range($currentStart, $currentStart + 2) as $startYear) {
            $years[] = $startYear.'/'.($startYear + 1);
        }

        $years = array_values(array_unique($years));
        rsort($years, SORT_STRING);

        return $years;
    }

    private function currentAcademicYear(): string
    {
        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        return $startYear.'/'.($startYear + 1);
    }
}
