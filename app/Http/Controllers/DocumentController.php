<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Support\LocalStoredFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function show(Request $request, string $type, int $id): BinaryFileResponse
    {
        abort_unless(in_array($request->user()->role, ['kepala_tu', 'tu', 'kepsek'], true), 403);
        $document = match ($type) {
            'incoming' => $this->visibleIncoming($request, $id),
            'outgoing' => OutgoingLetter::query()->findOrFail($id),
            'disposition' => $this->visibleDisposition($request, $id),
            default => abort(404),
        };
        $path = match ($type) {
            'disposition' => $document->attachment_path,
            default => $document->file_path,
        };
        $absolutePath = $path ? LocalStoredFile::resolve($path) : null;
        abort_unless($absolutePath && is_file($absolutePath), 404);

        $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        $headers = ['Content-Type' => $mimeType, 'X-Content-Type-Options' => 'nosniff'];
        $downloadName = match ($type) {
            'incoming' => $this->downloadName('surat-masuk', $document->agenda_number, $document->subject, $path),
            'outgoing' => $this->downloadName('surat-keluar', $document->agenda_number, $document->subject, $path),
            'disposition' => $this->dispositionDownloadName($document, $path),
        };

        return $request->boolean('download')
            ? response()->download($absolutePath, $downloadName, $headers)
            : response()->file($absolutePath, $headers);
    }

    private function dispositionDownloadName(Disposition $disposition, string $path): string
    {
        $letter = $disposition->incomingLetter ?? $disposition->outgoingLetter;

        return $this->downloadName(
            'lampiran-disposisi',
            $letter?->agenda_number,
            $letter?->subject,
            $path,
        );
    }

    private function downloadName(string $prefix, ?string $agenda, ?string $subject, string $path): string
    {
        $parts = array_filter([$prefix, Str::slug($agenda ?? ''), Str::limit(Str::slug($subject ?? ''), 70, '')]);

        return implode('-', $parts).'.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private function visibleIncoming(Request $request, int $id): IncomingLetter
    {
        $letter = IncomingLetter::query()->findOrFail($id);
        abort_unless($letter->isVisibleTo($request->user()), 403, 'Surat ini berklasifikasi rahasia dan tidak dapat diakses oleh akun Anda.');

        return $letter;
    }

    private function visibleDisposition(Request $request, int $id): Disposition
    {
        $disposition = Disposition::query()->with('incomingLetter')->findOrFail($id);
        abort_unless(! $disposition->incomingLetter || $disposition->incomingLetter->isVisibleTo($request->user()), 403, 'Surat ini berklasifikasi rahasia dan tidak dapat diakses oleh akun Anda.');

        return $disposition;
    }
}
