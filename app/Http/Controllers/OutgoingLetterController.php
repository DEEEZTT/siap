<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OutgoingLetter;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OutgoingLetterController extends Controller
{
    private const SORTABLE_COLUMNS = ['agenda_number', 'type', 'letter_date', 'recipient_name', 'subject', 'created_by', 'status'];

    private const TYPES = [
        'undangan' => ['label' => 'Surat undangan', 'description' => 'Mengundang penerima ke kegiatan sekolah.', 'subject' => 'Undangan'],
        'perintah_tugas' => ['label' => 'Surat perintah tugas', 'description' => 'Menugaskan pegawai untuk melaksanakan tugas sekolah.', 'subject' => 'Perintah Tugas'],
        'rekomendasi_mutasi' => ['label' => 'Surat rekomendasi mutasi/pindah siswa', 'description' => 'Memberikan rekomendasi perpindahan siswa ke sekolah tujuan.', 'subject' => 'Rekomendasi Mutasi/Pindah Siswa'],
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', 'in:undangan,perintah_tugas,rekomendasi_mutasi'],
            'status' => ['nullable', 'in:draft,menunggu_verifikasi,perlu_revisi,menunggu_persetujuan_kepsek,perlu_revisi_kepsek,siap_dicetak,selesai'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        $firstLetterDate = OutgoingLetter::query()->whereNotNull('letter_date')->min('letter_date');
        $latestLetterDate = OutgoingLetter::query()->whereNotNull('letter_date')->max('letter_date');
        $firstYear = $firstLetterDate ? (int) substr((string) $firstLetterDate, 0, 4) : (int) now()->format('Y');
        $lastYear = max((int) now()->format('Y'), $latestLetterDate ? (int) substr((string) $latestLetterDate, 0, 4) : $firstYear);
        $years = range($lastYear, $firstYear);
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $searchTerm = $request->string('q')->toString();
        $matchesUnassignedNumberLabel = $request->filled('q')
            && Str::contains(Str::lower('Nomor dibuat saat verifikasi'), Str::lower($searchTerm));

        $letters = OutgoingLetter::query()
            ->with('creator')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('year'), function ($query) use ($request): void {
                $year = $request->integer('year');
                $start = CarbonImmutable::create($year, $request->filled('month') ? $request->integer('month') : 1, 1);
                $end = $request->filled('month') ? $start->endOfMonth() : $start->endOfYear();

                $query->whereBetween('letter_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->when(! $request->filled('year') && $request->filled('month'), fn ($query) => $query->whereMonth('letter_date', $request->integer('month')))
            ->when($request->filled('q'), function ($query) use ($searchTerm, $matchesUnassignedNumberLabel): void {
                $query->where(function ($search) use ($searchTerm, $matchesUnassignedNumberLabel): void {
                    $pattern = '%'.$searchTerm.'%';
                    $search->where('recipient_name', 'like', $pattern)
                        ->orWhere('subject', 'like', $pattern)
                        ->orWhere('agenda_number', 'like', $pattern)
                        ->orWhere('letter_number', 'like', $pattern)
                        ->orWhere('type', 'like', $pattern)
                        ->orWhere('status', 'like', $pattern)
                        ->orWhere('letter_date', 'like', $pattern)
                        ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'like', $pattern));

                    if ($matchesUnassignedNumberLabel) {
                        $search->orWhereNull('letter_number');
                    }
                });
            })
            ->when($request->filled('sort'), function ($query) use ($request): void {
                $sort = $request->string('sort')->toString();
                $direction = $request->input('direction', 'asc');
                if ($sort === 'created_by') {
                    $query->orderBy(\App\Models\User::query()->select('name')->whereColumn('users.id', 'outgoing_letters.created_by')->limit(1), $direction);
                } else {
                    $query->orderBy($sort, $direction);
                }
                $query->orderByDesc('id');
            }, fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('outgoing.index', compact('letters', 'years', 'months'));
    }

    public function create(): View
    {
        return view('outgoing.types', ['types' => self::TYPES]);
    }

    public function createType(string $type): View
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $viewData = ['type' => $type, 'typeLabel' => self::TYPES[$type]['label'], 'defaultSubject' => self::TYPES[$type]['subject']];

        if ($type === 'rekomendasi_mutasi') {
            $viewData['students'] = Student::query()->latest('created_at')->limit(300)->get(['nis', 'name', 'birth_place', 'birth_date']);
        }

        return view('outgoing.create', $viewData);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate($this->rulesForType($request->input('type')));
        $attributes = collect($data)->except('action')->all();

        if ($data['type'] === 'undangan') {
            $attributes = [...$attributes, ...$this->invitationDefaults($data['details']['grade_class'])];
        }
        if ($data['type'] === 'perintah_tugas') {
            $attributes = $this->normalizeTaskParticipants($attributes);
        }

        $letter = OutgoingLetter::query()->create([...$attributes, 'created_by' => $request->user()->id, 'status' => $data['action'] === 'submit' ? 'menunggu_verifikasi' : 'draft']);

        $message = $data['action'] === 'submit' ? 'Surat dikirim ke Kepala TU untuk diverifikasi.' : 'Draft surat berhasil disimpan.';
        $destination = $data['action'] === 'draft'
            ? route('outgoing.index')
            : route('outgoing.show', $letter);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $destination, 'message' => $message], 201);
        }

        return redirect($destination)->with('success', $message);
    }

    public function show(Request $request, OutgoingLetter $outgoing): View
    {
        $outgoing->load(['creator', 'verifier', 'principalReviewer', 'disposition.completer']);

        if ($request->ajax()) {
            return view('outgoing.preview', compact('outgoing'));
        }

        return view('outgoing.show', compact('outgoing'));
    }

    public function edit(OutgoingLetter $outgoing): View
    {
        abort_unless(in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek']), 403);

        $type = self::TYPES[$outgoing->type] ?? self::TYPES['undangan'];

        $viewData = ['outgoing' => $outgoing, 'type' => $outgoing->type, 'typeLabel' => $type['label'], 'defaultSubject' => $type['subject']];

        if ($outgoing->type === 'rekomendasi_mutasi') {
            $viewData['students'] = Student::query()->latest('created_at')->limit(300)->get(['nis', 'name', 'birth_place', 'birth_date']);
        }

        return view('outgoing.edit', $viewData);
    }

    public function update(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek']), 403);
        $data = $request->validate($this->rulesForType($outgoing->type, includeType: false));
        $attributes = collect($data)->except('action')->all();

        if ($outgoing->type === 'undangan') {
            $attributes = [...$attributes, ...$this->invitationDefaults($data['details']['grade_class'])];
        }
        if ($outgoing->type === 'perintah_tugas') {
            $attributes = $this->normalizeTaskParticipants($attributes);
        }

        $outgoing->update([...$attributes, 'status' => $data['action'] === 'submit' ? 'menunggu_verifikasi' : 'draft', 'verification_note' => null]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Surat berhasil diperbarui.', 'status' => $outgoing->status]);
        }

        $destination = $data['action'] === 'draft'
            ? route('outgoing.index')
            : route('outgoing.show', $outgoing);

        return redirect($destination)->with('success', 'Surat berhasil diperbarui.');
    }

    public function submitForVerification(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['tu', 'kepala_tu'], true), 403);

        DB::transaction(function () use ($outgoing): void {
            $lockedOutgoing = OutgoingLetter::query()->whereKey($outgoing->getKey())->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedOutgoing->status, ['draft', 'perlu_revisi', 'perlu_revisi_kepsek'], true), 409, 'Surat ini tidak dapat dikirim untuk verifikasi.');
            $lockedOutgoing->update(['status' => 'menunggu_verifikasi', 'verification_note' => null]);
        }, attempts: 5);

        ActivityLog::record('surat_keluar_dikirim_verifikasi', 'Mengirim surat keluar #'.$outgoing->id.' ke Kepala TU untuk diverifikasi.', $outgoing);
        $message = 'Surat dikirim ke Kepala TU untuk diverifikasi.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('outgoing.index')->with('success', $message);
    }

    public function verify(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:setuju,revisi'], 'verification_note' => ['nullable', 'string', 'max:2000']]);
        if ($data['decision'] === 'revisi') {
            $request->validate(
                ['verification_note' => ['required', 'string', 'max:2000']],
                ['verification_note.required' => 'Catatan untuk TU wajib diisi jika meminta revisi.'],
            );
        }

        DB::transaction(function () use ($data, $outgoing, $request): void {
            $outgoing = OutgoingLetter::query()->whereKey($outgoing->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($outgoing->status === 'menunggu_verifikasi', 409);

            if ($data['decision'] === 'revisi') {
                $outgoing->update(['status' => 'perlu_revisi', 'verification_note' => $data['verification_note'], 'verified_by' => $request->user()->id, 'verified_at' => now()]);

                return;
            }

            $year = now()->year;
            $number = $this->nextAgendaNumber('outgoing', $year);

            $outgoing->update([
                'status' => 'menunggu_persetujuan_kepsek',
                'verification_note' => $data['verification_note'] ?? null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'letter_date' => now()->toDateString(),
                'agenda_number' => 'SK/'.$year.'/'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'letter_number' => '400.3/'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'/SMKN4/'.substr((string) $year, -4),
            ]);
        }, attempts: 5);

        ActivityLog::record('surat_keluar_verifikasi', 'Verifikasi surat keluar #'.$outgoing->id.': '.$data['decision'].'.', $outgoing);

        $message = $data['decision'] === 'setuju'
            ? 'Surat diverifikasi dan diteruskan ke Kepala Sekolah untuk persetujuan.'
            : 'Surat dikembalikan kepada pembuat untuk diperbaiki.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function principalReview(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['kepsek', 'kepala_tu'], true), 403, 'Persetujuan surat keluar hanya dapat dilakukan oleh Kepala Sekolah atau Kepala TU.');

        $data = $request->validate([
            'decision' => ['required', 'in:setuju,revisi'],
            'kepsek_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'revisi') {
            $request->validate(
                ['kepsek_note' => ['required', 'string', 'max:2000']],
                ['kepsek_note.required' => 'Catatan revisi wajib diisi sebelum surat dikembalikan ke TU.'],
            );
        }

        DB::transaction(function () use ($request, $data, $outgoing): void {
            $lockedOutgoing = OutgoingLetter::query()->whereKey($outgoing->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($lockedOutgoing->status === 'menunggu_persetujuan_kepsek', 409, 'Surat tidak sedang menunggu persetujuan Kepala Sekolah.');

            if ($data['decision'] === 'revisi') {
                $lockedOutgoing->update([
                    'status' => 'perlu_revisi_kepsek',
                    'kepsek_note' => $data['kepsek_note'],
                    'kepsek_reviewed_by' => $request->user()->id,
                    'kepsek_reviewed_at' => now(),
                ]);

                return;
            }

            $lockedOutgoing->update([
                'status' => 'siap_dicetak',
                'kepsek_note' => $data['kepsek_note'] ?? null,
                'kepsek_reviewed_by' => $request->user()->id,
                'kepsek_reviewed_at' => now(),
            ]);
        });

        ActivityLog::record('surat_keluar_persetujuan', 'Persetujuan Kepala Sekolah atas surat keluar #'.$outgoing->id.': '.$data['decision'].'.', $outgoing);

        $message = $data['decision'] === 'setuju'
            ? 'Surat disetujui Kepala Sekolah dan siap dicetak.'
            : 'Surat dikembalikan ke Tata Usaha untuk direvisi.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function print(Request $request, OutgoingLetter $outgoing)
    {
        if ($request->boolean('preview')) {
            $kepriLogo = public_path('images/kepri-pdf.png');
            $schoolLogo = public_path('images/smkn4-pdf.png');
            abort_unless(is_file($kepriLogo) && is_file($schoolLogo), 500, 'Logo surat tidak ditemukan.');

            return view('outgoing.pdf', [
                'outgoing' => $outgoing,
                'logoKepri' => 'data:image/png;base64,'.base64_encode((string) file_get_contents($kepriLogo)),
                'logoSmkn4' => 'data:image/png;base64,'.base64_encode((string) file_get_contents($schoolLogo)),
                'screenPreview' => true,
            ]);
        }

        $kepriLogo = public_path('images/kepri-pdf.png');
        $schoolLogo = public_path('images/smkn4-pdf.png');
        abort_unless(is_file($kepriLogo) && is_file($schoolLogo), 500, 'Logo surat tidak ditemukan.');

        $filename = implode('-', array_filter([
            'surat-keluar',
            Str::slug($outgoing->agenda_number ?? 'draft'),
            Str::limit(Str::slug($outgoing->subject), 70, ''),
        ])).'.pdf';
        $storage = Storage::disk('local');
        $pdfPath = 'outgoing-previews/'.$outgoing->id.'.pdf';
        $versionPath = 'outgoing-previews/'.$outgoing->id.'.version';
        $version = hash('sha256', implode('|', [
            $outgoing->updated_at?->format('U.u') ?? '',
            (string) filemtime($kepriLogo),
            (string) filemtime($schoolLogo),
            (string) hash_file('sha256', resource_path('views/outgoing/pdf.blade.php')),
            (string) hash_file('sha256', resource_path('views/outgoing/document.blade.php')),
        ]));
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($storage->exists($pdfPath) && $storage->exists($versionPath) && trim($storage->get($versionPath)) === $version) {
            $cachedPdf = \App\Support\LocalStoredFile::resolve($pdfPath);
            if ($cachedPdf) {
                return response()->file($cachedPdf, $headers);
            }
        }

        $pdf = Pdf::loadView('outgoing.pdf', [
            'outgoing' => $outgoing,
            'logoKepri' => 'data:image/png;base64,'.base64_encode((string) file_get_contents($kepriLogo)),
            'logoSmkn4' => 'data:image/png;base64,'.base64_encode((string) file_get_contents($schoolLogo)),
        ])
            ->setPaper('a4', 'portrait')
            ->output();

        $storage->put($pdfPath, $pdf);
        $storage->put($versionPath, $version);

        return response($pdf, 200, $headers);
    }

    public function complete(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['tu', 'kepala_tu'], true), 403);

        DB::transaction(function () use ($outgoing): void {
            $lockedOutgoing = OutgoingLetter::query()->whereKey($outgoing->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($lockedOutgoing->status === 'siap_dicetak', 409, 'Surat hanya dapat ditandai selesai setelah disetujui dan siap dicetak.');

            $lockedOutgoing->update(['status' => 'selesai']);
        });

        ActivityLog::record('surat_keluar_selesai', 'Surat keluar #'.$outgoing->id.' ditandai selesai setelah dicetak/dikirim.', $outgoing);
        $message = 'Surat ditandai selesai.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'status' => 'selesai']);
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, OutgoingLetter $outgoing): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['tu', 'kepala_tu'], true), 403);
        abort_unless(in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek', 'selesai']), 403);
        abort_unless($user->role === 'kepala_tu' || $outgoing->created_by === $user->id, 403);
        $dispositionFile = $outgoing->disposition?->attachment_path;
        if ($outgoing->file_path) {
            Storage::disk('local')->delete($outgoing->file_path);
        }
        if ($dispositionFile) {
            Storage::disk('local')->delete($dispositionFile);
        }
        ActivityLog::record('surat_keluar_dihapus', 'Menghapus surat keluar #'.$outgoing->id.'.', $outgoing);
        $outgoing->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Surat keluar dihapus.']);
        }

        return redirect()->route('outgoing.index')->with('success', 'Surat keluar dihapus.');
    }

    private function nextAgendaNumber(string $type, int $year): int
    {
        DB::table('agenda_sequences')->insertOrIgnore([
            'sequence_type' => $type,
            'sequence_year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('agenda_sequences')
            ->where('sequence_type', $type)
            ->where('sequence_year', $year)
            ->lockForUpdate()
            ->firstOrFail();

        $number = (int) $sequence->last_number + 1;

        DB::table('agenda_sequences')
            ->where('sequence_type', $type)
            ->where('sequence_year', $year)
            ->update(['last_number' => $number, 'updated_at' => now()]);

        return $number;
    }

    /** @return array<string, list<string>> */
    private function rulesForType(?string $type, bool $includeType = true): array
    {
        $rules = [
            'notes' => ['nullable', 'string', 'max:2000'],
            'action' => ['required', 'in:draft,submit'],
        ];

        if ($includeType) {
            $rules['type'] = ['required', 'in:'.implode(',', array_keys(self::TYPES))];
        }

        if ($type === 'undangan') {
            return $rules + [
                'details' => ['required', 'array:invitation_context,grade_class,attachments'],
                'details.invitation_context' => ['required', 'string', 'max:1000'],
                'details.grade_class' => ['required', 'string', 'max:80'],
                'details.attachments' => ['nullable', 'string', 'max:500'],
                'event_date' => ['required', 'date'],
                'event_time' => ['required', 'string', 'max:30'],
                'event_place' => ['required', 'string', 'max:190'],
            ];
        }

        if ($type === 'perintah_tugas') {
            return $rules + ['recipient_name' => ['required', 'string', 'max:190'], 'subject' => ['required', 'string', 'max:190']] + [
                'details' => ['required', 'array:basis,rank,employee_number,position,assignment,participants,recipients'],
                'details.basis' => ['required', 'string', 'max:2000'],
                'details.rank' => ['nullable', 'string', 'max:120'],
                'details.employee_number' => ['nullable', 'string', 'max:120'],
                'details.position' => ['nullable', 'string', 'max:190'],
                'details.assignment' => ['required', 'string', 'max:10000'],
                'details.participants' => ['sometimes', 'nullable', 'array', 'max:50'],
                'details.participants.*' => ['array:name,class'],
                'details.participants.*.name' => ['nullable', 'string', 'max:190'],
                'details.participants.*.class' => ['nullable', 'string', 'max:120'],
                'details.recipients' => ['sometimes', 'nullable', 'array', 'max:20'],
                'details.recipients.*' => ['array:name,rank,employee_number,position'],
                'details.recipients.*.name' => ['nullable', 'string', 'max:190'],
                'details.recipients.*.rank' => ['nullable', 'string', 'max:120'],
                'details.recipients.*.employee_number' => ['nullable', 'string', 'max:120'],
                'details.recipients.*.position' => ['nullable', 'string', 'max:190'],
            ];
        }

        if ($type === 'rekomendasi_mutasi') {
            return $rules + ['recipient_name' => ['required', 'string', 'max:190'], 'subject' => ['required', 'string', 'max:190']] + [
                'details' => ['required', 'array:nisn,origin_school,destination_school,class_info,origin_school_address,birth_place,birth_date,father_name,father_occupation,mother_name,mother_occupation,student_profile,reason,attachments'],
                'details.nisn' => ['required', 'string', 'max:30'],
                'details.origin_school' => ['required', 'string', 'max:190'],
                'details.destination_school' => ['required', 'string', 'max:190'],
                'details.class_info' => ['required', 'string', 'max:120'],
                'details.origin_school_address' => ['required', 'string', 'max:500'],
                'details.birth_place' => ['required', 'string', 'max:120'],
                'details.birth_date' => ['required', 'date'],
                'details.father_name' => ['nullable', 'string', 'max:190'],
                'details.father_occupation' => ['nullable', 'string', 'max:190'],
                'details.mother_name' => ['nullable', 'string', 'max:190'],
                'details.mother_occupation' => ['nullable', 'string', 'max:190'],
                'details.student_profile' => ['nullable', 'string', 'max:2000'],
                'details.reason' => ['nullable', 'string', 'max:3000'],
                'details.attachments' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return $rules;
    }

    /** @return array{recipient_name: string, recipient_address: string, subject: string} */
    private function invitationDefaults(string $gradeClass): array
    {
        return [
            'recipient_name' => 'Orang tua/Wali Murid kelas '.$gradeClass,
            'recipient_address' => 'di Tanjungpinang',
            'subject' => 'Undangan',
        ];
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function normalizeTaskParticipants(array $attributes): array
    {
        $primaryRecipient = [
            'name' => trim($attributes['recipient_name'] ?? ''),
            'rank' => trim($attributes['details']['rank'] ?? ''),
            'employee_number' => trim($attributes['details']['employee_number'] ?? ''),
            'position' => trim($attributes['details']['position'] ?? ''),
        ];
        $recipients = [$primaryRecipient];
        foreach ($attributes['details']['recipients'] ?? [] as $recipient) {
            $recipient = [
                'name' => trim($recipient['name'] ?? ''),
                'rank' => trim($recipient['rank'] ?? ''),
                'employee_number' => trim($recipient['employee_number'] ?? ''),
                'position' => trim($recipient['position'] ?? ''),
            ];
            if ($recipient['name'] !== '') {
                $recipients[] = $recipient;
            }
        }

        $participants = array_map(static fn (array $participant): array => [
            'name' => trim($participant['name'] ?? ''),
            'class' => trim($participant['class'] ?? ''),
        ], $attributes['details']['participants'] ?? []);
        $attributes['details']['participants'] = array_values(array_filter(
            $participants,
            static fn (array $participant): bool => $participant['name'] !== '',
        ));
        $attributes['details']['recipients'] = $recipients;

        return $attributes;
    }
}
