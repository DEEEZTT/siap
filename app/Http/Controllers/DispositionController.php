<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Support\UploadedDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DispositionController extends Controller
{
    private const RECIPIENTS = [
        'Waka Kurikulum',
        'Waka Kesiswaan',
        'Waka Sarpras',
        'Waka Humas',
        'Pembina OSIS',
        'Koordinator TU',
        'Bendahara Sekolah/BOS',
        'Bendahara SPP',
    ];

    private const INSTRUCTIONS = [
        'Tanggapan dan saran',
        'Proses lebih lanjut',
        'Koordinasi/konfirmasi',
    ];

    public function incoming(Request $request, IncomingLetter $incoming): RedirectResponse
    {
        $data = $this->validated($request);
        $storedPath = null;

        try {
            DB::transaction(function () use ($request, $data, $incoming, &$storedPath): void {
                $lockedIncoming = IncomingLetter::query()->whereKey($incoming->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($lockedIncoming->status === 'menunggu_disposisi', 409, 'Surat sudah diproses.');

                $this->saveIncomingDisposition($request, $lockedIncoming, $data, $storedPath);
                $lockedIncoming->update(['status' => 'menunggu_konfirmasi_tu']);
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        return back()->with('success', 'Disposisi tersimpan. Menunggu konfirmasi tindak lanjut dari Tata Usaha.');
    }

    public function confirmIncomingFollowUp(Request $request, IncomingLetter $incoming): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['confirmation_note' => ['nullable', 'string', 'max:2000']]);

        DB::transaction(function () use ($request, $data, $incoming): void {
            $lockedIncoming = IncomingLetter::query()->whereKey($incoming->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($lockedIncoming->status === 'menunggu_konfirmasi_tu', 409, 'Surat tidak sedang menunggu konfirmasi Tata Usaha.');

            $disposition = $lockedIncoming->disposition()->firstOrFail();
            $before = ['status' => $lockedIncoming->status, 'confirmation_note' => $disposition->confirmation_note];
            $disposition->forceFill([
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'confirmation_note' => $data['confirmation_note'] ?? null,
            ]);
            $lockedIncoming->update(['status' => 'selesai']);
            $this->appendHistory($disposition, $request, 'tindak_lanjut_dikonfirmasi', $before, [
                'status' => 'selesai',
                'confirmation_note' => $disposition->confirmation_note,
            ]);
            $disposition->save();
        });

        $message = 'Tindak lanjut surat dikonfirmasi dan status surat menjadi selesai.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => 'selesai'])
            : back()->with('success', $message);
    }

    public function printIncoming(IncomingLetter $incoming): View
    {
        $disposition = $incoming->disposition()->firstOrFail();

        return view('dispositions.print', [
            'disposition' => $disposition,
            'sender' => $incoming->sender,
            'received' => $incoming->received_at->format('d/m/Y'),
            'number' => $incoming->letter_number,
            'agenda' => $incoming->agenda_number,
            'date' => $incoming->letter_date->format('d/m/Y'),
            'classification' => $incoming->classification,
            'subject' => $incoming->subject,
            'letterUrl' => route('incoming.show', $incoming),
        ]);
    }

    public function printOutgoing(OutgoingLetter $outgoing): View
    {
        $disposition = $outgoing->disposition()->firstOrFail();

        return view('dispositions.print', [
            'disposition' => $disposition,
            'sender' => $outgoing->recipient_name,
            'received' => $outgoing->letter_date?->format('d/m/Y') ?? '—',
            'number' => $outgoing->letter_number ?? '—',
            'agenda' => $outgoing->agenda_number ?? '—',
            'date' => $outgoing->letter_date?->format('d/m/Y') ?? '—',
            'classification' => 'biasa',
            'subject' => $outgoing->subject,
            'letterUrl' => route('outgoing.show', $outgoing),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'recipients' => ['nullable', 'array', 'min:1', 'max:8', 'required_without:other_recipient'],
            'recipients.*' => ['string', Rule::in(self::RECIPIENTS)],
            'other_recipient' => ['nullable', 'required_without:recipients', 'string', 'max:190'],
            'instruction' => ['required', 'string', Rule::in([...self::INSTRUCTIONS, 'lainnya'])],
            'other_instruction' => ['nullable', 'required_if:instruction,lainnya', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'max:10240', function ($attribute, $file, $fail): void {
                if (! UploadedDocument::extension($file)) {
                    $fail('File harus berupa PDF, JPG, JPEG, atau PNG yang valid.');
                }
            }],
        ], [
            'recipients.required_without' => 'Pilih minimal satu penerima atau isi penerima lainnya.',
            'recipients.min' => 'Pilih minimal satu penerima.',
            'other_recipient.required_without' => 'Pilih minimal satu penerima atau isi penerima lainnya.',
            'instruction.required' => 'Pilih arahan disposisi terlebih dahulu.',
            'other_instruction.required_if' => 'Isi arahan disposisi lainnya.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveIncomingDisposition(
        Request $request,
        IncomingLetter $incoming,
        array $data,
        ?string &$storedPath,
    ): void {
        $disposition = $incoming->disposition()->firstOrNew();
        abort_if($disposition->exists, 409, 'Disposisi sudah dibuat dan tidak dapat diubah.');

        if ($request->hasFile('attachment')) {
            $newAttachmentPath = UploadedDocument::store($request->file('attachment'), 'dispositions');
            abort_if($newAttachmentPath === false, 500, 'Lampiran disposisi gagal disimpan.');
            $storedPath = $newAttachmentPath;
            $disposition->attachment_path = $storedPath;
        }

        $disposition->fill([
            'recipients' => $data['recipients'] ?? [],
            'other_recipient' => $data['other_recipient'] ?? null,
            'instructions' => isset($data['instruction']) && $data['instruction'] !== 'lainnya' ? [$data['instruction']] : [],
            'other_instruction' => ($data['instruction'] ?? null) === 'lainnya' ? $data['other_instruction'] : null,
            'note' => $data['note'] ?? null,
            'completed_by' => $request->user()->id,
            'completed_at' => now(),
            'confirmed_by' => null,
            'confirmed_at' => null,
            'confirmation_note' => null,
        ]);

        $after = $this->dispositionSnapshot($disposition);
        $disposition->save();
        $this->appendHistory($disposition, $request, 'disposisi_dibuat', null, $after);
        $disposition->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function dispositionSnapshot(Disposition $disposition): array
    {
        return [
            'recipients' => $disposition->recipients ?? [],
            'other_recipient' => $disposition->other_recipient,
            'instructions' => $disposition->instructions ?? [],
            'other_instruction' => $disposition->other_instruction,
            'note' => $disposition->note,
            'has_attachment' => (bool) $disposition->attachment_path,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    private function appendHistory(Disposition $disposition, Request $request, string $action, ?array $before, array $after): void
    {
        $history = $disposition->revision_history ?? [];
        $history[] = [
            'action' => $action,
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'user_role' => $request->user()->role,
            'at' => now()->toIso8601String(),
            'before' => $before,
            'after' => $after,
        ];

        $disposition->revision_history = $history;
    }
}
