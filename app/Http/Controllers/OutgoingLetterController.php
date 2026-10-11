<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OutgoingLetter;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OutgoingLetterController extends Controller
{
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
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('recipient_name', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('recipient_address', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('subject', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('agenda_number', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('letter_number', 'like', '%'.$request->string('q')->toString().'%')))
            ->latest()
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rulesForType($request->input('type')));
        $attributes = collect($data)->except('action')->all();

        if ($data['type'] === 'undangan') {
            $attributes = [...$attributes, ...$this->invitationDefaults($data['details']['grade_class'])];
        }

        $letter = OutgoingLetter::query()->create([...$attributes, 'created_by' => $request->user()->id, 'status' => $data['action'] === 'submit' ? 'menunggu_verifikasi' : 'draft']);

        return redirect()->route('outgoing.show', $letter)->with('success', $data['action'] === 'submit' ? 'Surat dikirim ke Kepala TU untuk diverifikasi.' : 'Draft surat berhasil disimpan.');
    }

    public function show(OutgoingLetter $outgoing): View
    {
        $outgoing->load(['creator', 'verifier', 'principalReviewer', 'disposition.completer']);

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

    public function update(Request $request, OutgoingLetter $outgoing): RedirectResponse
    {
        abort_unless(in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek']), 403);
        $data = $request->validate($this->rulesForType($outgoing->type, includeType: false));
        $attributes = collect($data)->except('action')->all();

        if ($outgoing->type === 'undangan') {
            $attributes = [...$attributes, ...$this->invitationDefaults($data['details']['grade_class'])];
        }

        $outgoing->update([...$attributes, 'status' => $data['action'] === 'submit' ? 'menunggu_verifikasi' : 'draft', 'verification_note' => null]);

        return redirect()->route('outgoing.show', $outgoing)->with('success', 'Surat berhasil diperbarui.');
    }

    public function verify(Request $request, OutgoingLetter $outgoing): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:setuju,revisi'], 'verification_note' => ['nullable', 'string', 'max:2000']]);
        if ($data['decision'] === 'revisi') {
            $request->validate(['verification_note' => ['required', 'string', 'max:2000']]);
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

        return back()->with('success', $data['decision'] === 'setuju' ? 'Surat diverifikasi dan diteruskan ke Kepala Sekolah untuk persetujuan.' : 'Surat dikembalikan kepada pembuat untuk diperbaiki.');
    }

    public function principalReview(Request $request, OutgoingLetter $outgoing): RedirectResponse
    {
        abort_unless($request->user()->role === 'kepsek', 403, 'Persetujuan surat keluar hanya dapat dilakukan oleh Kepala Sekolah.');

        $data = $request->validate([
            'decision' => ['required', 'in:setuju,revisi'],
            'kepsek_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'revisi') {
            $request->validate(['kepsek_note' => ['required', 'string', 'max:2000']]);
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

        return back()->with('success', $data['decision'] === 'setuju' ? 'Surat disetujui Kepala Sekolah dan siap dicetak.' : 'Surat dikembalikan ke Tata Usaha untuk direvisi.');
    }

    public function print(OutgoingLetter $outgoing): View
    {
        return view('outgoing.print', compact('outgoing'));
    }

    public function destroy(OutgoingLetter $outgoing): RedirectResponse
    {
        $user = request()->user();
        abort_unless(in_array($user->role, ['tu', 'kepala_tu'], true), 403);
        abort_unless(in_array($outgoing->status, ['draft', 'perlu_revisi', 'perlu_revisi_kepsek', 'selesai']), 403);
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
                'details' => ['required', 'array:invitation_context,grade_class'],
                'details.invitation_context' => ['required', 'string', 'max:1000'],
                'details.grade_class' => ['required', 'string', 'max:80'],
                'event_date' => ['required', 'date'],
                'event_time' => ['required', 'string', 'max:30'],
                'event_place' => ['required', 'string', 'max:190'],
            ];
        }

        if ($type === 'perintah_tugas') {
            return $rules + ['recipient_name' => ['required', 'string', 'max:190'], 'subject' => ['required', 'string', 'max:190']] + [
                'details' => ['required', 'array:basis,rank,employee_number,position,assignment'],
                'details.basis' => ['required', 'string', 'max:2000'],
                'details.rank' => ['nullable', 'string', 'max:120'],
                'details.employee_number' => ['nullable', 'string', 'max:120'],
                'details.position' => ['nullable', 'string', 'max:190'],
                'details.assignment' => ['required', 'string', 'max:10000'],
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
}
