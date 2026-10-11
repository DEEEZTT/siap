@extends('layouts.app')
@section('title', 'Sertifikat kegiatan')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">SERTIFIKAT</span><h1>Sertifikat kegiatan</h1><p>Penghargaan, peserta kegiatan, narasumber, dan sertifikat lain dari template sekolah.</p></div>
    <div class="row-actions">
        @if($certificates->total() > 0)<a class="button button-light" href="{{ route('event-certificates.print_all') }}">Cetak semua</a>@endif
        <a class="button" href="{{ route('event-certificates.create') }}">＋ Terbitkan sertifikat</a>
    </div>
</div>
<div class="toolbar">
    <form class="filter-panel" method="get">
        <div class="filter-main">
            <label class="filter-search"><span class="sr-only">Cari sertifikat</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nama penerima, nomor, atau judul"></label>
            <select class="select" name="category"><option value="">Semua jenis</option>@foreach(\App\Models\Certificate::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select>
            <button class="button" type="submit">Cari data</button>
            @if(request()->hasAny(['q', 'category']))<a class="filter-reset" href="{{ route('event-certificates.index') }}">Bersihkan</a>@endif
        </div>
    </form>
</div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><th>Nomor</th><th>Penerima</th><th>Judul</th><th>Jenis</th><th>Tanggal</th><th></th></tr></thead><tbody>
@forelse($certificates as $certificate)
<tr><td>{{ $certificate->certificate_number }}</td><td><strong>{{ $certificate->recipient_name }}</strong><small>{{ $certificate->recipient_detail }}</small></td><td>{{ $certificate->title }}</td><td>{{ \App\Models\Certificate::CATEGORIES[$certificate->category] ?? $certificate->category }}</td><td>{{ $certificate->issued_at->format('d/m/Y') }}</td>
<td><a class="table-action" href="{{ route('event-certificates.show', $certificate) }}">Lihat / cetak</a></td></tr>
@empty
<tr><td colspan="6"><div class="empty-state">Belum ada sertifikat kegiatan.</div></td></tr>
@endforelse
</tbody></table></div><div class="pagination"><span>{{ $certificates->total() }} sertifikat</span>@include('components.pagination', ['paginator' => $certificates])</div></div>
@endsection
