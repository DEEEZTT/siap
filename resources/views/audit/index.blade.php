@extends('layouts.app')
@section('title', 'Jejak audit')
@section('content')
<div class="page-heading"><div><span class="eyebrow blue">PENGATURAN · KEAMANAN</span><h1>Jejak audit</h1><p>Riwayat perubahan penting: akun, verifikasi surat, sertifikat, dan penghapusan data.</p></div></div>
<div class="toolbar">
    <form class="filter-panel" method="get">@if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">@endif<div class="filter-main">
        <label class="filter-search"><span class="sr-only">Cari aktivitas</span><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari uraian aktivitas"></label>
        <select class="select" name="action"><option value="">Semua aktivitas</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ str_replace('_', ' ', $action) }}</option>@endforeach</select>
        <button class="button" type="submit">Cari data</button>
        @if(request()->hasAny(['q', 'action']))<a class="filter-reset" href="{{ route('audit.index') }}">Bersihkan</a>@endif
    </div></form>
</div>
<div class="table-card"><div class="table-scroll"><table><thead><tr><x-sortable-th column="created_at" label="Waktu" /><x-sortable-th column="user_id" label="Pengguna" /><x-sortable-th column="action" label="Aktivitas" /><x-sortable-th column="description" label="Uraian" /><x-sortable-th column="ip_address" label="IP" /></tr></thead><tbody>
@forelse($logs as $log)
<tr><td>{{ $log->created_at->format('d/m/Y H:i') }}</td><td>{{ $log->user?->name ?? 'Sistem / akun dihapus' }}</td><td><span class="status">{{ str_replace('_', ' ', $log->action) }}</span></td><td>{{ $log->description }}</td><td>{{ $log->ip_address }}</td></tr>
@empty
<tr><td colspan="5"><div class="empty-state">Belum ada aktivitas tercatat.</div></td></tr>
@endforelse
</tbody></table></div><div class="pagination"><span>{{ $logs->total() }} catatan</span>@include('components.pagination', ['paginator' => $logs])</div></div>
@endsection
