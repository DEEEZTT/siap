<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function show(Student $student): View
    {
        abort_unless($student->completed_at, 404, 'Sertifikat siswa belum diterbitkan.');
        $student->load('company');

        return view('certificates.show', compact('student'));
    }


    public function downloadAll(): View
    {
        $students = Student::query()
            ->whereNotNull('completed_at')
            ->with('company')
            ->orderBy('certificate_number')
            ->orderBy('id')
            ->get();

        abort_if($students->isEmpty(), 404, 'Belum ada sertifikat yang diterbitkan.');

        $missingSignatoryCount = $students->filter(fn (Student $student): bool =>
            blank($student->certificate_signer_name) || blank($student->certificate_signer_title)
        )->count();

        return view('certificates.download-all', compact('students', 'missingSignatoryCount'));
    }

    public function updateSignatory(Request $request, Student $student): RedirectResponse
    {
        abort_unless($student->completed_at, 404, 'Sertifikat siswa belum diterbitkan.');

        $student->update($request->validate([
            'certificate_signer_name' => ['required', 'string', 'max:190'],
            'certificate_signer_title' => ['required', 'string', 'max:190'],
            'certificate_signer_nip' => ['nullable', 'string', 'max:50'],
        ]));

        return redirect()->route('certificates.show', $student)->with('success', 'Data penanda tangan mitra berhasil disimpan.');
    }
}
