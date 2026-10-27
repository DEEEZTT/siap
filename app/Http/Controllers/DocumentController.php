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
        $disk = Storage::disk('local');
        $absolutePath = $path ? $disk->path($path) : null;
        abort_unless($absolutePath && is_file($absolutePath), 404);

        $mimeType = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        $headers = ['Content-Type' => $mimeType, 'X-Content-Type-Options' => 'nosniff'];
        return $request->boolean('download')
            ? response()->download($absolutePath, basename($path), $headers)
            : response()->file($absolutePath, $headers);
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
