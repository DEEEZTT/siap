<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\IncomingLetter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IncomingLetterController extends Controller
{
    private const SORTABLE_COLUMNS = ['agenda_number', 'received_at', 'sender', 'subject', 'classification', 'status'];

    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:menunggu_disposisi,menunggu_konfirmasi_tu,selesai'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE_COLUMNS)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $firstReceivedAt = IncomingLetter::query()->min('received_at');
        $latestReceivedAt = IncomingLetter::query()->max('received_at');
        $firstYear = $firstReceivedAt ? (int) substr((string) $firstReceivedAt, 0, 4) : (int) now()->format('Y');
        $lastYear = max((int) now()->format('Y'), $latestReceivedAt ? (int) substr((string) $latestReceivedAt, 0, 4) : $firstYear);
        $years = range($lastYear, $firstYear);
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $letters = IncomingLetter::query()
            ->visibleTo($request->user())
            ->with('creator')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('year'), function ($query) use ($request): void {
                $year = $request->integer('year');
                $start = CarbonImmutable::create($year, $request->filled('month') ? $request->integer('month') : 1, 1);
                $end = $request->filled('month') ? $start->endOfMonth() : $start->endOfYear();

                $query->whereBetween('received_at', [$start->toDateString(), $end->toDateString()]);
            })
            ->when(! $request->filled('year') && $request->filled('month'), fn ($query) => $query->whereMonth('received_at', $request->integer('month')))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('agenda_number', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('letter_number', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('sender', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('subject', 'like', '%'.$request->string('q')->toString().'%')))
            ->when($request->filled('sort'), function ($query) use ($request): void {
                $query->orderBy($request->string('sort')->toString(), $request->input('direction', 'asc'))
                    ->orderByDesc('id');
            }, fn ($query) => $query->latest('received_at'))
            ->paginate(12)
            ->withQueryString();

        return view('incoming.index', compact('letters', 'months', 'years'));
    }

    public function create(): View
    {
        return view('incoming.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['letter_number' => ['required', 'string', 'max:120'], 'letter_date' => ['required', 'date'], 'received_at' => ['required', 'date'], 'sender' => ['required', 'string', 'max:190'], 'subject' => ['required', 'string', 'max:190'], 'classification' => ['required', 'in:biasa,segera,sangat_segera,rahasia'], 'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $year = (int) substr($data['received_at'], 0, 4);
        $filePath = $request->file('file')?->store('letters/incoming', 'local');
        $letterData = collect($data)->except('file')->all();

        $letter = DB::transaction(function () use ($letterData, $filePath, $request, $year): IncomingLetter {
            $number = $this->nextAgendaNumber('incoming', $year);

            return IncomingLetter::query()->create([
                'agenda_number' => 'IM/'.$year.'/'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                ...$letterData,
                'file_path' => $filePath,
                'created_by' => $request->user()->id,
                'status' => 'menunggu_disposisi',
            ]);
        }, attempts: 5);

        ActivityLog::record('surat_masuk_dicatat', 'Mencatat surat masuk '.$letter->agenda_number.' ('.$letter->classification.').', $letter);

        return redirect()->route('incoming.index')->with('success', 'Surat masuk dicatat dan diteruskan ke kepala sekolah.');
    }

    public function show(IncomingLetter $incoming): View
    {
        $incoming->load(['creator', 'disposition.completer', 'disposition.confirmer']);

        return view('incoming.show', compact('incoming'));
    }

    public function edit(IncomingLetter $incoming): View
    {
        abort_unless(in_array($incoming->status, ['menunggu_disposisi', 'perlu_perbaikan'], true), 403);

        return view('incoming.edit', compact('incoming'));
    }

    public function update(Request $request, IncomingLetter $incoming): RedirectResponse
    {
        abort_unless(in_array($incoming->status, ['menunggu_disposisi', 'perlu_perbaikan'], true), 403);
        $data = $request->validate(['letter_number' => ['required', 'string', 'max:120'], 'letter_date' => ['required', 'date'], 'received_at' => ['required', 'date'], 'sender' => ['required', 'string', 'max:190'], 'subject' => ['required', 'string', 'max:190'], 'classification' => ['required', 'in:biasa,segera,sangat_segera,rahasia'], 'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], 'notes' => ['nullable', 'string', 'max:2000']]);
        if ($request->hasFile('file')) {
            $oldFile = $incoming->file_path;
            $data['file_path'] = $request->file('file')->store('letters/incoming', 'local');

        } unset($data['file']);
        $incoming->update($data);

        if (isset($oldFile)) {
            Storage::disk('local')->delete($oldFile);
        }

        return redirect()->route('incoming.show', $incoming)->with('success', 'Data surat diperbarui.');
    }

    public function destroy(Request $request, IncomingLetter $incoming): RedirectResponse
    {
        abort_unless($request->user()->role === 'kepala_tu', 403);
        $dispositionFile = $incoming->disposition?->attachment_path;
        if ($incoming->file_path) {
            Storage::disk('local')->delete($incoming->file_path);
        }
        if ($dispositionFile) {
            Storage::disk('local')->delete($dispositionFile);
        }
        ActivityLog::record('surat_masuk_dihapus', 'Menghapus surat masuk '.$incoming->agenda_number.'.', $incoming);
        $incoming->delete();

        return redirect()->route('incoming.index')->with('success', 'Surat dihapus.');
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
}
