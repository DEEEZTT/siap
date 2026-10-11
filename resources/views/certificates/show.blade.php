@extends('layouts.app')

@section('title', 'Sertifikat PKL')

@section('content')
    <div class="page-heading hide-print">
        <div>
            <span class="eyebrow blue">SERTIFIKAT PRAKTIK KERJA LAPANGAN</span>
            <h1>{{ $student->name }}</h1>
            <p>Pratinjau sertifikat lanskap sesuai ukuran karya aslinya.</p>
        </div>
        <a class="button button-light" href="{{ route('students.index') }}">← Daftar siswa</a>
    </div>

    <section class="form-card certificate-editor hide-print">
        <div class="panel-heading">
            <div>
                <span class="eyebrow blue">PENANDA TANGAN MITRA PKL</span>
                <h2>Lengkapi pejabat di sisi kanan sertifikat</h2>
                <p class="certificate-editor-note">Nama dan jabatan dicetak pada sertifikat. Tanda tangan dan cap diisi manual setelah dicetak.</p>
            </div>
        </div>
        <form method="post" action="{{ route('certificates.signatory.update', $student) }}">
            @csrf
            @method('put')
            <div class="certificate-editor-grid">
                <div class="field">
                    <label for="certificate-signer-name">Nama penanda tangan <span class="required">*</span></label>
                    <input class="input" id="certificate-signer-name" name="certificate_signer_name" value="{{ old('certificate_signer_name', $student->certificate_signer_name) }}" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="certificate-signer-title">Jabatan <span class="required">*</span></label>
                    <input class="input" id="certificate-signer-title" name="certificate_signer_title" value="{{ old('certificate_signer_title', $student->certificate_signer_title) }}" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="certificate-signer-nip">NIP <span class="field-hint">(opsional)</span></label>
                    <input class="input" id="certificate-signer-nip" name="certificate_signer_nip" value="{{ old('certificate_signer_nip', $student->certificate_signer_nip) }}" maxlength="50">
                </div>
            </div>
            <div class="form-actions">
                <button class="button">Simpan data penanda tangan</button>
            </div>
        </form>
    </section>

    <div class="print-toolbar hide-print">
        @if($student->certificate_signer_name && $student->certificate_signer_title)
            <button class="button" onclick="window.print()">Cetak sertifikat / Simpan PDF</button>
        @else
            <button class="button" disabled>Isi nama dan jabatan sebelum mencetak</button>
        @endif
    </div>

    @if(in_array(auth()->user()->role, ['humas', 'kepala_tu'], true))
        <div class="danger-zone hide-print">
            <p class="field-hint">Menghapus data siswa ini juga menghapus sertifikat PKL yang terbit.</p>
            <form method="post" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus data {{ $student->name }} beserta sertifikat PKL-nya? Tindakan ini tidak bisa dibatalkan.')">
                @csrf
                @method('delete')
                <button class="button button-danger" type="submit">Hapus siswa dan sertifikat</button>
            </form>
        </div>
    @endif

    @include('certificates.partials.certificate', ['student' => $student])

@endsection
