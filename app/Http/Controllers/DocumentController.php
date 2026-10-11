<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(Request $request, string $type, int $id): BinaryFileResponse|StreamedResponse
    {
        abort_unless(in_array($request->user()->role, ['kepala_tu', 'tu', 'kepsek'], true), 403);
        $path = match ($type) {
            'incoming' => $this->visibleIncoming($request, $id)->file_path,
            'outgoing' => OutgoingLetter::query()->findOrFail($id)->file_path,
            'disposition' => $this->visibleDisposition($request, $id)->attachment_path,
            default => abort(404),
        };
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return $request->boolean('download') ? Storage::disk('local')->download($path) : response()->file(Storage::disk('local')->path($path), ['Content-Type' => Storage::disk('local')->mimeType($path) ?: 'application/octet-stream']);
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
