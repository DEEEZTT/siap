@extends('layouts.app')
@section('title', 'Surat keluar')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">BUKU AGENDA SURAT KELUAR</span><h1>Surat keluar</h1><p>Surat melewati pemeriksaan Kepala TU dan persetujuan Kepala Sekolah sebelum dicetak.</p></div>
    @if(in_array(auth()->user()->role, ['tu', 'kepala_tu'], true))
        <a class="button" href="{{ route('outgoing.create') }}">＋ Buat surat</a>
    @endif
</div>
<div class="toolbar">
    <form class="filter-panel" method="get">
        <div class="filter-main">
            <label class="filter-search"><span class="filter-search-icon" aria-hidden="true">⌕</span><span class="sr-only">Cari surat keluar</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari penerima, nomor surat, atau perihal"></label>
            <button class="button" type="submit"><span aria-hidden="true">⌕</span> Cari data</button>
            @if(request()->hasAny(['q', 'status', 'type', 'year', 'month']))<a class="filter-reset" href="{{ route('outgoing.index') }}">Bersihkan</a>@endif
        </div>
        <details class="filter-details" @if(request()->filled('status') || request()->filled('type') || request()->filled('year') || request()->filled('month')) open @endif>
            <summary><span class="filter-summary-icon" aria-hidden="true">☷</span> Filter lanjutan <span class="filter-summary-hint">Jenis, status, dan tanggal surat</span></summary>
            <div class="filter-options">
                <label class="filter-field"><span>Jenis surat</span><select class="select" name="type">
                    <option value="">Semua jenis</option>
                    <option value="undangan" @selected(request('type') === 'undangan')>Undangan</option>
                    <option value="perintah_tugas" @selected(request('type') === 'perintah_tugas')>Perintah tugas</option>
                    <option value="rekomendasi_mutasi" @selected(request('type') === 'rekomendasi_mutasi')>Rekomendasi mutasi</option>
                </select></label>
                <label class="filter-field"><span>Status surat</span><select class="select" name="status">
                    <option value="">Semua status</option>
                    @foreach(['draft', 'menunggu_verifikasi', 'perlu_revisi', 'menunggu_persetujuan_kepsek', 'perlu_revisi_kepsek', 'siap_dicetak', 'selesai'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select></label>
                <label class="filter-field"><span>Tahun surat</span><select class="select" name="year"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach</select></label>
                <label class="filter-field"><span>Bulan surat</span><select class="select" name="month"><option value="">Semua bulan</option>@foreach($months as $number => $month)<option value="{{ $number }}" @selected((string) request('month') === (string) $number)>{{ $month }}</option>@endforeach</select></label>
            </div>
        </details>
    </form>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead><tr><th>No. agenda</th><th>Jenis</th><th>Tanggal</th><th>Tujuan</th><th>Perihal</th><th>Pembuat</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($letters as $letter)
                    <tr>
                        <td><strong>{{ $letter->agenda_number ?? 'Draft' }}</strong><small>{{ $letter->letter_number ?? 'Nomor dibuat saat verifikasi' }}</small></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $letter->type)) }}</td>
                        <td>{{ $letter->letter_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $letter->recipient_name }}</td>
                        <td>{{ $letter->subject }}</td>
                        <td>{{ $letter->creator?->name }}</td>
                        <td><span class="status status-{{ $letter->status }}">{{ str_replace('_', ' ', $letter->status) }}</span></td>
                        <td><div class="row-actions"><a class="table-action" href="{{ route('outgoing.show', $letter) }}">Buka</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state">Belum ada surat keluar untuk filter ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination"><span>{{ $letters->total() }} surat</span>@include('components.pagination', ['paginator' => $letters])</div>
</div>
@endsection
