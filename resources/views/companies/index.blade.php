@extends('layouts.app')
@section('title', 'Mitra perusahaan')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">HUMAS · KERJA SAMA</span><h1>Mitra perusahaan</h1><p>Kelola dokumen MoU/PKS, ruang lingkup, masa berlaku, dan status kerja sama tiap mitra.</p></div>
    <div class="row-actions">
        @if($companies->total() > 0)
            <a class="button button-light" href="{{ route('companies.export', array_filter(['q' => request('q'), 'type' => $type === 'all' ? null : $type, 'status' => request('status'), 'validity' => request('validity')])) }}">Ekspor Excel</a>
        @endif
        <a class="button" href="{{ route('companies.create') }}">＋ Tambah mitra</a>
    </div>
</div>
<div class="toolbar">
    <form class="filter-panel" method="get">
        <div class="filter-main">
            <label class="filter-search"><span class="filter-search-icon" aria-hidden="true">⌕</span><span class="sr-only">Cari mitra perusahaan</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nama, kontak, telepon, atau email perusahaan"></label>
            <button class="button" type="submit"><span aria-hidden="true">⌕</span> Cari data</button>
            @if(request()->hasAny(['q', 'type', 'status', 'validity']))<a class="filter-reset" href="{{ route('companies.index') }}">Bersihkan</a>@endif
        </div>
        <details class="filter-details" @if(request()->filled('type') || request()->filled('status') || request()->filled('validity')) open @endif>
            <summary><span class="filter-summary-icon" aria-hidden="true">☷</span> Filter lanjutan <span class="filter-summary-hint">Jenis kemitraan dan status kerja sama</span></summary>
            <div class="filter-options">
                <label class="filter-field"><span>Jenis kemitraan</span><select class="select" name="type"><option value="">Semua jenis ({{ $counts['all'] }})</option><option value="mou" @selected($type === 'mou')>MOU resmi ({{ $counts['mou'] }})</option><option value="pkl_only" @selected($type === 'pkl_only')>Mitra PKL saja ({{ $counts['pkl_only'] }})</option><option value="unclassified" @selected($type === 'unclassified')>Belum diklasifikasikan ({{ $counts['unclassified'] }})</option></select></label>
                <label class="filter-field"><span>Masa berlaku MoU/PKS</span><select class="select" name="validity"><option value="">Semua</option>@foreach(['berlaku' => 'Berlaku', 'segera_berakhir' => 'Segera berakhir (≤ 90 hari)', 'kedaluwarsa' => 'Kedaluwarsa', 'belum_lengkap' => 'Data belum lengkap'] as $value => $label)<option value="{{ $value }}" @selected(request('validity') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="filter-field"><span>Status kerja sama</span><select class="select" name="status"><option value="">Semua status</option><option value="aktif" @selected(request('status') === 'aktif')>Aktif</option><option value="tidak_aktif" @selected(request('status') === 'tidak_aktif')>Tidak aktif</option></select></label>
            </div>
        </details>
    </form>
</div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><th>Perusahaan</th><th>Jenis kemitraan</th><th>Dokumen MoU/PKS</th><th>Masa berlaku</th><th>Kontak</th><th>Telepon / email</th><th>Siswa PKL</th><th>Status kerja sama</th><th></th></tr></thead><tbody>
@forelse($companies as $company)
<tr>
    <td><strong>{{ $company->name }}</strong><small>{{ $company->address }}</small>@if($company->mou_document_path)<small><a class="text-link" href="{{ route('companies.mou.download', $company) }}">Unduh PDF MOU</a></small>@endif</td>
    <td><span class="status {{ $company->partnership_type === 'mou' ? 'status-selesai' : ($company->partnership_type === 'pkl_only' ? 'status-draft' : 'status-menunggu_verifikasi') }}">{{ $company->agreementLabel() }}</span></td>
    <td>@if($company->isFormalAgreement())<strong>{{ $company->document_number ?: '—' }}</strong><small>{{ \Illuminate\Support\Str::limit($company->scope, 70) }}</small>@else — @endif</td>
    @php $agreementStatus = $company->agreementStatus(); @endphp
    <td>@if($agreementStatus)<span class="status {{ in_array($agreementStatus, ['berlaku']) ? 'status-selesai' : (in_array($agreementStatus, ['segera_berakhir', 'belum_lengkap', 'belum_berlaku']) ? 'status-menunggu_verifikasi' : 'status-perlu_revisi') }}">{{ \App\Models\Company::agreementStatusLabels()[$agreementStatus] }}</span><small>{{ $company->valid_from?->format('d/m/Y') }} – {{ $company->valid_until?->format('d/m/Y') }}</small>@else — @endif</td>
    <td>{{ $company->contact_person ?: '—' }}</td>
    <td>{{ $company->phone ?: '—' }}<small>{{ $company->email }}</small></td>
    <td>{{ $company->students_count }}</td>
    <td><span class="status {{ $company->status === 'aktif' ? 'status-selesai' : 'status-draft' }}">{{ str_replace('_', ' ', $company->status) }}</span></td>
    <td><div class="row-actions"><a class="table-action" href="{{ route('companies.edit', $company) }}">Ubah</a></div></td>
</tr>
@empty
<tr><td colspan="9"><div class="empty-state">Tidak ada data mitra untuk filter ini.</div></td></tr>
@endforelse
</tbody></table></div><div class="pagination"><span>{{ $companies->total() }} mitra</span>@include('components.pagination', ['paginator' => $companies])</div></div>
@endsection
