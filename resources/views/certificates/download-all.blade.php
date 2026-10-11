@extends('layouts.app')

@section('title', 'Unduh sertifikat terbit')

@section('content')
    <div class="page-heading hide-print">
        <div>
            <span class="eyebrow blue">SERTIFIKAT PRAKTIK KERJA LAPANGAN</span>
            <h1>{{ $students->count() }} sertifikat siap disimpan</h1>
            <p>Ini hanya sertifikat yang sudah diterbitkan. Status dan data PKL tidak diubah.</p>
        </div>
    </div>

    @if($missingSignatoryCount > 0)
        <div class="alert alert-error hide-print">
            {{ $missingSignatoryCount }} sertifikat belum memiliki nama atau jabatan penanda tangan mitra. Data yang kosong tidak akan tercetak.
        </div>
    @endif

    <div class="print-toolbar hide-print">
        <button class="button" type="button" onclick="window.print()">Cetak / Simpan semua sebagai PDF</button>
        <a class="button button-light" href="{{ route('students.index') }}">Kembali ke data siswa</a>
        <span>Pada dialog cetak, pilih “Simpan sebagai PDF” untuk mengunduh satu file berisi seluruh sertifikat.</span>
    </div>

    @foreach($students as $student)
        <div class="certificate-batch-item">
            @include('certificates.partials.certificate', ['student' => $student])
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', () => window.setTimeout(() => window.print(), 500));
    </script>
@endpush
