<div class="pkl-certificate-batch-preview">
    <div class="page-heading hide-print">
        <div>
            <span class="eyebrow blue">SERTIFIKAT PRAKTIK KERJA LAPANGAN</span>
            <h1>{{ $students->count() }} sertifikat terbit</h1>
            <p>Semua sertifikat yang sudah diterbitkan disiapkan dalam satu dokumen PDF.</p>
        </div>
        <button class="button" type="button" onclick="window.print()">Cetak / Simpan semua sebagai PDF</button>
    </div>

    @if($missingSignatoryCount > 0)
        <div class="alert alert-error hide-print">
            {{ $missingSignatoryCount }} sertifikat belum memiliki nama atau jabatan penanda tangan mitra. Lengkapi datanya agar sertifikat siap dicetak.
        </div>
    @endif

    <div class="pkl-certificate-batch-list">
        @foreach($students as $student)
            <div class="certificate-batch-item">
                @include('certificates.partials.certificate', ['student' => $student])
            </div>
        @endforeach
    </div>
</div>
