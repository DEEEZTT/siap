@extends('layouts.app')
@section('title', 'Data siswa PKL')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">HUMAS · PRAKTIK KERJA LAPANGAN</span><h1>Data siswa PKL</h1><p>Kelola masa PKL, tandai siswa selesai, lalu cetak sertifikat.</p></div>
    <div class="row-actions">
        @if($issuedCertificateCount > 0)
            <div class="bulk-certificate-form">
                <a class="button bulk-certificate-button" href="{{ route('certificates.download_all') }}">Unduh semua sertifikat terbit <span>{{ $issuedCertificateCount }}</span></a>
                <small>Sertifikat terbit · semua tahun · simpan sebagai PDF</small>
            </div>
        @endif
        @if($students->total() > 0)
            <a class="button button-light" href="{{ route('students.export', array_filter(['q' => request('q'), 'academic_year' => $selectedAcademicYear, 'status' => request('status')])) }}">Ekspor Excel</a>
        @endif
        <a class="button button-light" href="{{ route('students.template') }}">Unduh template Excel</a><a class="button" href="{{ route('students.create') }}">＋ Tambah siswa</a>
    </div>
</div>
<div class="form-card" style="max-width:none;margin-bottom:16px">
    <form method="post" action="{{ route('students.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <div class="field"><label for="import-academic-year">Tahun pelajaran</label><select class="select" id="import-academic-year" name="academic_year" required>@foreach($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected(old('academic_year', $selectedAcademicYear) === $academicYear)>{{ $academicYear }}</option>@endforeach</select></div>
            <div class="field"><label for="student-file">Berkas Excel (.xlsx atau .csv)</label><input class="input" id="student-file" type="file" name="file" accept=".xlsx,.csv" required><span class="field-hint">Gunakan header sesuai template. Konsentrasi keahlian: RPL, GIM, PSPT, Animasi, TKJ, atau DKV. Tanggal dd/mm/yyyy; predikat Cukup, Baik, atau Sangat Baik.</span></div>
        </div>
        <div class="form-actions"><button class="button">Impor data siswa</button></div>
    </form>
</div>
<div class="toolbar">
    <form class="filter-panel" method="get">
        <div class="filter-main">
            <label class="filter-search"><span class="filter-search-icon" aria-hidden="true">⌕</span><span class="sr-only">Cari data siswa PKL</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nama, NIS, jurusan, atau perusahaan"></label>
            <button class="button" type="submit"><span aria-hidden="true">⌕</span> Cari data</button>
            @if(request()->hasAny(['q', 'academic_year', 'status']))<a class="filter-reset" href="{{ route('students.index') }}">Bersihkan</a>@endif
        </div>
        <details class="filter-details" @if(request()->filled('status')) open @endif>
            <summary><span class="filter-summary-icon" aria-hidden="true">☷</span> Filter lanjutan <span class="filter-summary-hint">Tahun pelajaran dan status PKL</span></summary>
            <div class="filter-options">
                <label class="filter-field"><span>Tahun pelajaran</span><select class="select" name="academic_year">@foreach($academicYears as $academicYear)<option value="{{ $academicYear }}" @selected($selectedAcademicYear === $academicYear)>{{ $academicYear }}</option>@endforeach</select></label>
                <label class="filter-field"><span>Status siswa</span><select class="select" name="status"><option value="">Semua siswa</option><option value="berjalan" @selected(request('status') === 'berjalan')>PKL berjalan</option><option value="selesai" @selected(request('status') === 'selesai')>Selesai</option></select></label>
            </div>
        </details>
    </form>
</div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><th>No.</th><th>Nama siswa</th><th>NIS / jurusan</th><th>Perusahaan</th><th>Masa PKL</th><th>Predikat</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($students as $student)
<tr>
    <td>{{ $students->firstItem() + $loop->index }}</td>
    <td><strong>{{ $student->name }}</strong></td>
    <td>{{ $student->nis }}<small>{{ $student->major }}</small></td>
    <td>{{ $student->company?->name ?? '—' }}</td>
    <td>{{ $student->internship_start?->format('d/m/Y') ?? '—' }} – {{ $student->internship_end?->format('d/m/Y') ?? '—' }}</td>
    <td>{{ $student->predicate ?: 'Belum dipilih' }}</td>
    <td><span class="status {{ $student->completed_at ? 'status-selesai' : 'status-draft' }}">{{ $student->completed_at ? 'Selesai' : 'Berlangsung' }}</span></td>
    <td><div class="row-actions student-actions">
        @if(!$student->completed_at)<a class="button button-light button-small" href="{{ route('students.edit', $student) }}">Ubah data</a>@endif
        @if($student->completed_at)
            <small title="Data siswa dengan sertifikat terbit dipertahankan agar sertifikat tetap dapat diakses.">Sertifikat terbit</small>
        @endif
        @if($student->completed_at)
            <a class="button button-light button-small" href="{{ route('certificates.show', $student) }}">Lihat sertifikat</a>
        @elseif($student->internship_end && $student->internship_end->isPast())
            @if(in_array($student->predicate, ['Cukup', 'Baik', 'Sangat Baik'], true))
                <form method="post" action="{{ route('students.complete', $student) }}" onsubmit="return confirm('Yakin terbitkan sertifikat PKL ini dengan predikat {{ $student->predicate }}?')">@csrf<input type="hidden" name="predicate" value="{{ $student->predicate }}"><button class="button button-small" type="submit" aria-label="Terbitkan sertifikat untuk {{ $student->name }}">Terbitkan sertifikat</button></form>
            @else
                <a class="button button-light button-small" href="{{ route('students.edit', $student) }}">Atur predikat</a>
            @endif
        @endif
    </div></td>
</tr>
@empty
<tr><td colspan="8"><div class="empty-state">Belum ada data siswa PKL untuk tahun pelajaran {{ $selectedAcademicYear }}.</div></td></tr>
@endforelse
</tbody></table></div><div class="pagination"><span>{{ $students->total() }} siswa · {{ $selectedAcademicYear }}</span>@include('components.pagination', ['paginator' => $students])</div></div>
@endsection
