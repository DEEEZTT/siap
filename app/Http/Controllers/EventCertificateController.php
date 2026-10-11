<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EventCertificateController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(Certificate::CATEGORIES))],
        ]);

        $certificates = Certificate::query()
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('recipient_name', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('certificate_number', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('title', 'like', '%'.$request->string('q')->toString().'%')))
            ->latest('issued_at')->latest('id')
            ->paginate(15)->withQueryString();

        return view('event-certificates.index', compact('certificates'));
    }

    public function create(): View
    {
        return view('event-certificates.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['recipients' => ['required', 'string', 'max:20000']] + $this->rules());
        $recipients = collect(preg_split('/\R/', $data['recipients']))->map(fn ($line) => trim((string) $line))->filter()->unique()->values();
        unset($data['recipients']);
        abort_if($recipients->isEmpty(), 422, 'Isi minimal satu nama penerima.');
        abort_if($recipients->count() > 200, 422, 'Maksimal 200 penerima sekali terbit.');

        $year = (int) substr($data['issued_at'], 0, 4);

        $created = DB::transaction(function () use ($recipients, $data, $year, $request): Collection {
            return $recipients->map(function (string $line) use ($data, $year, $request): Certificate {
                [$name, $detail] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);
                $number = $this->nextNumber($year);

                return Certificate::query()->create([
                    ...$data,
                    'recipient_name' => $name,
                    'recipient_detail' => $detail ?: null,
                    'certificate_number' => 'SRT/'.$year.'/'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    'created_by' => $request->user()->id,
                ]);
            });
        }, attempts: 5);

        ActivityLog::record('sertifikat_kegiatan_terbit', 'Menerbitkan '.$created->count().' sertifikat "'.$data['title'].'".', $created->first());

        return redirect()->route('event-certificates.index')->with('success', $created->count().' sertifikat berhasil diterbitkan.');
    }

    public function show(Certificate $certificate): View
    {
        return view('event-certificates.show', compact('certificate'));
    }

    public function printAll(Request $request): View
    {
        $request->validate(['title' => ['nullable', 'string', 'max:190'], 'issued_at' => ['nullable', 'date']]);
        $certificates = Certificate::query()
            ->when($request->filled('title'), fn ($query) => $query->where('title', $request->string('title')->toString()))
            ->when($request->filled('issued_at'), fn ($query) => $query->whereDate('issued_at', $request->string('issued_at')->toString()))
            ->orderBy('certificate_number')->get();

        abort_if($certificates->isEmpty(), 404, 'Tidak ada sertifikat untuk dicetak.');

        return view('event-certificates.print-all', compact('certificates'));
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        abort_unless(request()->user()->role === 'kepala_tu', 403, 'Hanya Kepala TU yang dapat menghapus sertifikat.');
        ActivityLog::record('sertifikat_kegiatan_dihapus', 'Menghapus sertifikat '.$certificate->certificate_number.' a.n. '.$certificate->recipient_name.'.', $certificate);
        $certificate->delete();

        return redirect()->route('event-certificates.index')->with('success', 'Sertifikat dihapus.');
    }

    /** @return array<string, list<string>> */
    private function rules(): array
    {
        return [
            'category' => ['required', 'in:'.implode(',', array_keys(Certificate::CATEGORIES))],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:1000'],
            'issued_at' => ['required', 'date'],
            'signer_name' => ['required', 'string', 'max:190'],
            'signer_title' => ['required', 'string', 'max:190'],
            'signer_nip' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function nextNumber(int $year): int
    {
        DB::table('agenda_sequences')->insertOrIgnore([
            'sequence_type' => 'certificate',
            'sequence_year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('agenda_sequences')->where('sequence_type', 'certificate')->where('sequence_year', $year)->lockForUpdate()->firstOrFail();
        $number = (int) $sequence->last_number + 1;
        DB::table('agenda_sequences')->where('sequence_type', 'certificate')->where('sequence_year', $year)->update(['last_number' => $number, 'updated_at' => now()]);

        return $number;
    }
}
