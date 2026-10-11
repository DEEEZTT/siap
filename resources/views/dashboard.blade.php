@extends('layouts.app')
@section('title', 'Ringkasan')
@section('content')
<div class="welcome-row">
    <div>
        <div class="eyebrow blue">{{ now()->translatedFormat('l, d F Y') }}</div>
        <h1>Selamat datang, {{ auth()->user()->name }}.</h1>
        <p class="muted">Ringkasan administrasi surat dan kegiatan PKL sekolah.</p>
    </div>
    <div class="welcome-seal">
        <img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
        <img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMKN 4">
    </div>
</div>

<div class="stat-grid">
    @if($canSeeIncoming)
        @php
            $incomingFilter = match (auth()->user()->role) {
                'kepsek' => 'menunggu_disposisi',
                'tu' => 'menunggu_konfirmasi_tu',
                default => null,
            };
        @endphp
        <a class="stat-card" href="{{ route('incoming.index', array_filter(['status' => $incomingFilter])) }}"><span class="stat-icon icon-blue">↙</span><span class="stat-label">Surat masuk</span><strong>{{ number_format($incomingCount) }}</strong><small>{{ $incomingPending }} {{ $incomingActionLabel }} · {{ $incomingOverdue }} lebih dari 3 hari</small></a>
    @endif
    @if($canSeeOutgoing)
        @php
            $outgoingFilter = auth()->user()->role === 'kepsek' ? 'menunggu_persetujuan_kepsek' : null;
        @endphp
        <a class="stat-card" href="{{ route('outgoing.index', array_filter(['status' => $outgoingFilter])) }}"><span class="stat-icon icon-amber">↗</span><span class="stat-label">Surat keluar</span><strong>{{ number_format($outgoingCount) }}</strong><small>{{ $outgoingPending }} {{ $outgoingActionLabel }} · {{ $outgoingOverdue }} lebih dari 3 hari</small></a>
    @endif
    @if($canSeePkl)
        <a class="stat-card" href="{{ route('students.index') }}"><span class="stat-icon icon-green">♙</span><span class="stat-label">Siswa PKL</span><strong>{{ number_format($studentCount) }}</strong><small>{{ $certificateCount }} sertifikat diterbitkan</small></a>
        <a class="stat-card" href="{{ route('companies.index') }}"><span class="stat-icon icon-purple">⌂</span><span class="stat-label">Mitra perusahaan</span><strong>{{ number_format($companyCount) }}</strong><small>{{ $agreementsExpiringCount }} MoU/PKS segera berakhir / kedaluwarsa</small></a>
    @endif
</div>

@php
    $panelCount = (int) $canSeeIncoming + (int) $canSeeOutgoing + ((int) $canSeePkl * 2);
@endphp
<div class="content-grid {{ $panelCount === 1 ? 'content-grid-single' : '' }}">
    @if($canSeeIncoming)
        <section class="panel">
            <div class="panel-heading">
                <div><span class="eyebrow">PERLU TINDAKAN</span><h2>Surat masuk</h2></div>
                <a class="text-link" href="{{ route('incoming.index') }}">Lihat semua →</a>
            </div>
            @forelse($recentIncoming as $letter)
                @php
                    $waitingSince = $letter->status === 'menunggu_konfirmasi_tu' ? $letter->disposition?->completed_at : $letter->received_at;
                    $waitingDays = $waitingSince ? (int) $waitingSince->diffInDays(now()) : 0;
                @endphp
                <a class="activity-row" href="{{ route('incoming.show', $letter) }}">
                    <span class="activity-icon">↙</span>
                    <span class="activity-copy"><b>{{ $letter->subject }}</b><small>{{ $letter->sender }} · {{ $letter->agenda_number }} · {{ $waitingDays }} hari</small></span>
                    <span class="status status-{{ $letter->status }}">{{ str_replace('_', ' ', $letter->status) }}</span>
                </a>
            @empty
                <div class="empty-state compact">Tidak ada surat masuk yang menunggu tindakan Anda.</div>
            @endforelse
        </section>
    @endif

    @if($canSeeOutgoing)
        <section class="panel">
            <div class="panel-heading">
                <div><span class="eyebrow">PERLU TINDAKAN</span><h2>Surat keluar</h2></div>
                <a class="text-link" href="{{ route('outgoing.index') }}">Lihat semua →</a>
            </div>
            @forelse($recentOutgoing as $letter)
                @php
                    $waitingSince = $letter->status === 'menunggu_persetujuan_kepsek' ? $letter->verified_at : $letter->updated_at;
                    $waitingDays = $waitingSince ? (int) $waitingSince->diffInDays(now()) : 0;
                @endphp
                <a class="activity-row" href="{{ route('outgoing.show', $letter) }}">
                    <span class="activity-icon activity-icon-amber">↗</span>
                    <span class="activity-copy"><b>{{ $letter->subject }}</b><small>{{ $letter->recipient_name }} · {{ $letter->agenda_number ?? 'Nomor belum dibuat' }} · {{ $waitingDays }} hari</small></span>
                    <span class="status status-{{ $letter->status }}">{{ str_replace('_', ' ', $letter->status) }}</span>
                </a>
            @empty
                <div class="empty-state compact">Tidak ada surat keluar yang menunggu tindakan Anda.</div>
            @endforelse
        </section>
    @endif

    @if($canSeePkl)
        <section class="panel">
            <div class="panel-heading">
                <div><span class="eyebrow">PERLU PERHATIAN</span><h2>Masa berlaku MoU/PKS</h2></div>
                <a class="text-link" href="{{ route('companies.index', ['validity' => 'segera_berakhir']) }}">Lihat semua →</a>
            </div>
            @forelse($agreementsExpiring as $company)
                @php $agreementStatus = $company->agreementStatus(); @endphp
                <a class="activity-row" href="{{ route('companies.edit', $company) }}">
                    <span class="activity-icon activity-icon-amber">⌂</span>
                    <span class="activity-copy"><b>{{ $company->name }}</b><small>{{ $company->document_number ?: 'Tanpa nomor' }} · berakhir {{ $company->valid_until?->format('d/m/Y') }}</small></span>
                    <span class="status {{ $agreementStatus === 'kedaluwarsa' ? 'status-perlu_revisi' : 'status-menunggu_verifikasi' }}">{{ \App\Models\Company::agreementStatusLabels()[$agreementStatus] ?? '' }}</span>
                </a>
            @empty
                <div class="empty-state compact">Tidak ada MoU/PKS yang akan berakhir dalam {{ \App\Models\Company::EXPIRING_WITHIN_DAYS }} hari.</div>
            @endforelse
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div><span class="eyebrow">DATA TERBARU</span><h2>Siswa PKL</h2></div>
                <a class="text-link" href="{{ route('students.index') }}">Lihat semua →</a>
            </div>
            @forelse($recentStudents as $student)
                <a class="activity-row" href="{{ route('students.index') }}">
                    <span class="activity-icon activity-icon-green">♙</span>
                    <span class="activity-copy"><b>{{ $student->name }}</b><small>{{ $student->company?->name ?? 'Perusahaan belum ditentukan' }} · {{ $student->academic_year }}</small></span>
                    <span class="status {{ $student->completed_at ? 'status-selesai' : 'status-menunggu_disposisi' }}">{{ $student->completed_at ? 'Sertifikat terbit' : 'PKL berjalan' }}</span>
                </a>
            @empty
                <div class="empty-state compact">Belum ada data siswa PKL.</div>
            @endforelse
        </section>
    @endif
</div>
@endsection
