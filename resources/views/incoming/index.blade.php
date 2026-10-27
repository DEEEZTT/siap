@extends('layouts.app')
@section('title', 'Surat masuk')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">BUKU AGENDA TATA USAHA</span><h1>Surat masuk</h1><p>Disposisi Kepala Sekolah dan konfirmasi tindak lanjut oleh Tata Usaha.</p></div>
    @if(in_array(auth()->user()->role, ['tu', 'kepala_tu'], true))
        <a class="button" href="{{ route('incoming.create') }}">＋ Catat surat masuk</a>
    @endif
</div>
<div class="toolbar">
    <form class="filter-panel" method="get">
        @if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">@endif
        <div class="filter-main">
            <label class="filter-search"><span class="filter-search-icon" aria-hidden="true">⌕</span><span class="sr-only">Cari surat masuk</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nomor surat, pengirim, atau perihal"></label>
            <button class="button" type="submit"><span aria-hidden="true">⌕</span> Cari data</button>
            @if(request()->hasAny(['q', 'status', 'year', 'month']))
                <a class="filter-reset" href="{{ route('incoming.index') }}">Bersihkan</a>
            @endif
        </div>
        <details class="filter-details" @if(request()->filled('status') || request()->filled('year') || request()->filled('month')) open @endif>
            <summary><span class="filter-summary-icon" aria-hidden="true">☷</span> Filter lanjutan <span class="filter-summary-hint">Status dan tanggal diterima</span></summary>
            <div class="filter-options">
                <label class="filter-field"><span>Status surat</span><select class="select" name="status">
                    <option value="">Semua status</option>
                    <option value="menunggu_disposisi" @selected(request('status') === 'menunggu_disposisi')>Menunggu disposisi</option>
                    <option value="menunggu_konfirmasi_tu" @selected(request('status') === 'menunggu_konfirmasi_tu')>Menunggu konfirmasi TU</option>
                    <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
                </select></label>
                <label class="filter-field"><span>Tahun diterima</span><select class="select" name="year">
                    <option value="">Semua tahun</option>
                    @foreach($years as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach
                </select></label>
                <label class="filter-field"><span>Bulan diterima</span><select class="select" name="month">
                    <option value="">Semua bulan</option>
                    @foreach($months as $number => $month)<option value="{{ $number }}" @selected((string) request('month') === (string) $number)>{{ $month }}</option>@endforeach
                </select></label>
            </div>
        </details>
    </form>
</div>
<div class="table-card">
    <div class="table-scroll">
        <table>
            <thead><tr>
                <x-sortable-th column="agenda_number" label="No. agenda / surat" />
                <x-sortable-th column="received_at" label="Diterima" />
                <x-sortable-th column="sender" label="Pengirim" />
                <x-sortable-th column="subject" label="Perihal" />
                <x-sortable-th column="classification" label="Sifat" />
                <x-sortable-th column="status" label="Status" />
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($letters as $letter)
                    <tr>
                        <td><strong>{{ $letter->agenda_number }}</strong><small>{{ $letter->letter_number }}</small></td>
                        <td>{{ $letter->received_at->format('d/m/Y') }}</td>
                        <td>{{ $letter->sender }}</td>
                        <td>{{ $letter->subject }}</td>
                        <td>{{ str_replace('_', ' ', $letter->classification) }}</td>
                        <td><span class="status status-{{ $letter->status }}">{{ str_replace('_', ' ', $letter->status) }}</span></td>
                        <td><div class="row-actions"><a class="table-action" href="{{ route('incoming.show', $letter) }}">Buka</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">Tidak ada surat untuk filter ini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination"><span>Menampilkan {{ $letters->firstItem() ?? 0 }}–{{ $letters->lastItem() ?? 0 }} dari {{ $letters->total() }} surat</span>@include('components.pagination', ['paginator' => $letters])</div>
</div>
@endsection
