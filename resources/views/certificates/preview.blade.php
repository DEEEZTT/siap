<div class="modal-record-preview pkl-certificate-modal-preview">
    <div class="page-heading pkl-certificate-heading">
        <div>
            <span class="eyebrow blue">SERTIFIKAT PRAKTIK KERJA LAPANGAN</span>
            <h1>{{ $student->name }}</h1>
            <div class="pkl-certificate-meta">
                <span><small>Nomor sertifikat</small><strong>{{ $student->certificate_number }}</strong></span>
                <span><small>Tahun pelajaran</small><strong>{{ $student->academic_year }}</strong></span>
                <span><small>Tempat PKL</small><strong>{{ $student->company?->name ?? 'Mitra Dunia Kerja' }}</strong></span>
            </div>
        </div>
        <div class="pkl-certificate-actions">
        @if($student->certificate_signer_name && $student->certificate_signer_title)
            <button class="button" type="button" onclick="window.print()"><span aria-hidden="true">▣</span> Cetak / Simpan PDF</button>
        @else
            <button class="button" type="button" disabled>Lengkapi penanda tangan</button>
        @endif
        </div>
    </div>

    <section class="form-card certificate-editor hide-print">
        <div class="panel-heading">
            <div>
                <span class="eyebrow blue">PENGATURAN SERTIFIKAT</span>
                <h2>Penanda tangan dari tempat PKL</h2>
                <p class="certificate-editor-note">Nama dan jabatan akan tercetak pada sertifikat.</p>
            </div>
        </div>
        <form class="certificate-signatory-form" method="post" action="{{ route('certificates.signatory.update', $student) }}">
            @csrf
            @method('put')
            <div class="certificate-editor-grid">
                <div class="field"><label for="certificate-signer-name">Nama penanda tangan <span class="required">*</span></label><input class="input" id="certificate-signer-name" name="certificate_signer_name" value="{{ old('certificate_signer_name', $student->certificate_signer_name) }}" maxlength="190" required></div>
                <div class="field"><label for="certificate-signer-title">Jabatan <span class="required">*</span></label><input class="input" id="certificate-signer-title" name="certificate_signer_title" value="{{ old('certificate_signer_title', $student->certificate_signer_title) }}" maxlength="190" required></div>
                <div class="field"><label for="certificate-signer-nip">NIP <span class="field-hint">(opsional)</span></label><input class="input" id="certificate-signer-nip" name="certificate_signer_nip" value="{{ old('certificate_signer_nip', $student->certificate_signer_nip) }}" maxlength="50"></div>
                <button class="button" type="submit">Simpan data penanda tangan</button>
            </div>
        </form>
    </section>

    @if(in_array(auth()->user()->role, ['humas', 'kepala_tu'], true))
        <div class="danger-zone hide-print">
            <p class="field-hint">Menghapus siswa ini juga menghapus sertifikat PKL yang terbit.</p>
            <form class="student-certificate-delete-form" method="post" action="{{ route('students.destroy', $student) }}">
                @csrf
                @method('delete')
                <button class="button button-danger" type="submit">Hapus siswa dan sertifikat</button>
            </form>
        </div>
    @endif

    <div class="pkl-certificate-stage">
        <div class="pkl-certificate-stage-heading hide-print"><strong>Pratinjau sertifikat</strong><span>Format lanskap · A4</span></div>
    @include('certificates.partials.certificate', ['student' => $student])
    </div>
</div>
