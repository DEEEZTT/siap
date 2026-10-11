@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    @php
        $periodLabel = ($month ? $months[$month].' ' : 'Tahun ').$year;
        $exportQuery = array_filter(['year' => $year, 'month' => $month]);
        $statusLabel = fn ($key) => ucfirst(str_replace('_', ' ', $key));
    @endphp

    <div class="page-heading hide-print report-page-heading">
        <div>
            <span class="eyebrow blue">PELAPORAN</span>
            <h1>Laporan administrasi</h1>
            <p>Ringkasan surat, sertifikat, dan dokumen kerja sama.</p>
        </div>
        <button class="button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <section class="report-filter-card hide-print" aria-label="Filter periode laporan">
        <div>
            <span class="report-filter-label">Periode laporan</span>
            <strong>{{ $periodLabel }}</strong>
        </div>
        <form class="report-filter-form" method="get">
            <label class="filter-field">
                <span>Tahun</span>
                <select class="select" name="year">
                    @foreach($years as $y)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endforeach
                </select>
            </label>
            <label class="filter-field">
                <span>Bulan</span>
                <select class="select" name="month">
                    <option value="">Seluruh tahun</option>
                    @foreach($months as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button" type="submit">Terapkan</button>
        </form>
    </section>

    <h2 class="print-only report-print-title">Laporan Administrasi SIAP — {{ $periodLabel }} · SMK Negeri 4 Tanjungpinang</h2>

    <section class="report-summary-grid" aria-label="Ringkasan laporan">
        @if($canSeeLetters)
            <article class="report-summary-card report-summary-blue">
                <span class="report-summary-icon" aria-hidden="true">↙</span>
                <div><span>Surat masuk</span><strong>{{ $incoming['total'] }}</strong><small>{{ $periodLabel }}</small></div>
            </article>
            <article class="report-summary-card report-summary-indigo">
                <span class="report-summary-icon" aria-hidden="true">↗</span>
                <div><span>Surat keluar</span><strong>{{ $outgoing['total'] }}</strong><small>{{ $periodLabel }}</small></div>
            </article>
        @endif
        @if($certificates)
            <article class="report-summary-card report-summary-green">
                <span class="report-summary-icon" aria-hidden="true">✦</span>
                <div><span>Sertifikat terbit</span><strong>{{ $certificates['pklTotal'] + $certificates['eventTotal'] }}</strong><small>{{ $periodLabel }}</small></div>
            </article>
        @endif
        @if($agreements)
            <article class="report-summary-card report-summary-amber">
                <span class="report-summary-icon" aria-hidden="true">▤</span>
                <div><span>Dokumen MoU / PKS</span><strong>{{ $agreements['total'] }}</strong><small>{{ $agreements['pklOnly'] }} mitra PKL saja · posisi terkini</small></div>
            </article>
        @endif
    </section>

    @if($canSeeLetters)
        @php
            $incomingPeak = max(1, max($incoming['byMonth']->all()));
            $incomingStatusTotal = max(1, $incoming['total']);
            $incomingClassTotal = max(1, $incoming['total']);
        @endphp
        <section class="panel report-panel">
            <div class="panel-heading report-panel-heading">
                <div><span class="eyebrow">SURAT MASUK</span><h2>Ringkasan surat masuk</h2><p>{{ $incoming['total'] }} surat tercatat · {{ $periodLabel }}</p></div>
                <a class="button button-light hide-print" href="{{ route('reports.export', ['section' => 'incoming'] + $exportQuery) }}">Ekspor Excel <span aria-hidden="true">↓</span></a>
            </div>
            <div class="report-insights-grid">
                <div class="report-breakdown-card">
                    <h3>Status surat</h3>
                    @forelse($incoming['byStatus'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>{{ $statusLabel($key) }}</span><strong>{{ $count }}</strong></div><span class="report-progress"><i style="width: {{ round($count / $incomingStatusTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada surat masuk pada periode ini.</p>
                    @endforelse
                </div>
                <div class="report-breakdown-card">
                    <h3>Sifat surat</h3>
                    @forelse($incoming['byClassification'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>{{ $statusLabel($key) }}</span><strong>{{ $count }}</strong></div><span class="report-progress report-progress-teal"><i style="width: {{ round($count / $incomingClassTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada data sifat surat.</p>
                    @endforelse
                </div>
            </div>
            <div class="report-chart-card">
                <div class="report-chart-heading"><div><h3>Aktivitas bulanan</h3><p>Jumlah surat yang diterima setiap bulan</p></div><span class="report-chart-total">{{ $incoming['total'] }} total</span></div>
                <div class="report-chart-scroll"><div class="report-bar-chart" role="img" aria-label="Grafik surat masuk per bulan">
                    @foreach($months as $number => $name)
                        @php $count = $incoming['byMonth'][$number] ?? 0; @endphp
                        <div class="report-bar-column" title="{{ $name }}: {{ $count }} surat">
                            <strong>{{ $count ?: '' }}</strong>
                            <div class="report-bar-track"><i class="report-bar-blue" style="height: {{ $count ? max(8, round($count / $incomingPeak * 100)) : 3 }}%"></i></div>
                            <span>{{ substr($name, 0, 3) }}</span>
                        </div>
                    @endforeach
                </div></div>
            </div>
        </section>

        @php
            $outgoingPeak = max(1, max($outgoing['byMonth']->all()));
            $outgoingStatusTotal = max(1, $outgoing['total']);
            $outgoingTypeTotal = max(1, $outgoing['total']);
        @endphp
        <section class="panel report-panel">
            <div class="panel-heading report-panel-heading">
                <div><span class="eyebrow">SURAT KELUAR</span><h2>Ringkasan surat keluar</h2><p>{{ $outgoing['total'] }} surat tercatat · {{ $periodLabel }}</p></div>
                <a class="button button-light hide-print" href="{{ route('reports.export', ['section' => 'outgoing'] + $exportQuery) }}">Ekspor Excel <span aria-hidden="true">↓</span></a>
            </div>
            <div class="report-insights-grid">
                <div class="report-breakdown-card">
                    <h3>Status proses</h3>
                    @forelse($outgoing['byStatus'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>{{ $statusLabel($key) }}</span><strong>{{ $count }}</strong></div><span class="report-progress"><i style="width: {{ round($count / $outgoingStatusTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada surat keluar pada periode ini.</p>
                    @endforelse
                </div>
                <div class="report-breakdown-card">
                    <h3>Jenis surat</h3>
                    @forelse($outgoing['byType'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>{{ $statusLabel($key) }}</span><strong>{{ $count }}</strong></div><span class="report-progress report-progress-indigo"><i style="width: {{ round($count / $outgoingTypeTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada data jenis surat.</p>
                    @endforelse
                </div>
            </div>
            <div class="report-chart-card">
                <div class="report-chart-heading"><div><h3>Aktivitas bulanan</h3><p>Jumlah surat yang dibuat setiap bulan</p></div><span class="report-chart-total">{{ $outgoing['total'] }} total</span></div>
                <div class="report-chart-scroll"><div class="report-bar-chart" role="img" aria-label="Grafik surat keluar per bulan">
                    @foreach($months as $number => $name)
                        @php $count = $outgoing['byMonth'][$number] ?? 0; @endphp
                        <div class="report-bar-column" title="{{ $name }}: {{ $count }} surat">
                            <strong>{{ $count ?: '' }}</strong>
                            <div class="report-bar-track"><i class="report-bar-indigo" style="height: {{ $count ? max(8, round($count / $outgoingPeak * 100)) : 3 }}%"></i></div>
                            <span>{{ substr($name, 0, 3) }}</span>
                        </div>
                    @endforeach
                </div></div>
            </div>
        </section>
    @endif

    @if($certificates)
        @php
            $certificatePeak = max(1, max($certificates['byMonth']->all()));
            $certificateTotal = max(1, $certificates['pklTotal'] + $certificates['eventTotal']);
        @endphp
        <section class="panel report-panel">
            <div class="panel-heading report-panel-heading">
                <div><span class="eyebrow">SERTIFIKAT</span><h2>Ringkasan sertifikat</h2><p>{{ $certificates['pklTotal'] + $certificates['eventTotal'] }} sertifikat terbit · {{ $periodLabel }}</p></div>
                <a class="button button-light hide-print" href="{{ route('reports.export', ['section' => 'certificates'] + $exportQuery) }}">Ekspor Excel <span aria-hidden="true">↓</span></a>
            </div>
            <div class="report-insights-grid">
                <div class="report-breakdown-card">
                    <h3>Sertifikat PKL · {{ $certificates['pklTotal'] }}</h3>
                    @forelse($certificates['pklByPredicate'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>Predikat {{ $key }}</span><strong>{{ $count }}</strong></div><span class="report-progress report-progress-green"><i style="width: {{ round($count / $certificateTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada sertifikat PKL pada periode ini.</p>
                    @endforelse
                </div>
                <div class="report-breakdown-card">
                    <h3>Sertifikat kegiatan · {{ $certificates['eventTotal'] }}</h3>
                    @forelse($certificates['eventByCategory'] as $key => $count)
                        <div class="report-breakdown-row"><div><span>{{ \App\Models\Certificate::CATEGORIES[$key] ?? $key }}</span><strong>{{ $count }}</strong></div><span class="report-progress report-progress-teal"><i style="width: {{ round($count / $certificateTotal * 100) }}%"></i></span></div>
                    @empty
                        <p class="report-empty">Belum ada sertifikat kegiatan pada periode ini.</p>
                    @endforelse
                </div>
            </div>
            <div class="report-chart-card">
                <div class="report-chart-heading"><div><h3>Sertifikat per bulan</h3><p>PKL dan kegiatan dalam periode terpilih</p></div><span class="report-chart-total">{{ $certificates['pklTotal'] + $certificates['eventTotal'] }} total</span></div>
                <div class="report-chart-scroll"><div class="report-bar-chart" role="img" aria-label="Grafik sertifikat per bulan">
                    @foreach($months as $number => $name)
                        @php $count = $certificates['byMonth'][$number] ?? 0; @endphp
                        <div class="report-bar-column" title="{{ $name }}: {{ $count }} sertifikat">
                            <strong>{{ $count ?: '' }}</strong>
                            <div class="report-bar-track"><i class="report-bar-green" style="height: {{ $count ? max(8, round($count / $certificatePeak * 100)) : 3 }}%"></i></div>
                            <span>{{ substr($name, 0, 3) }}</span>
                        </div>
                    @endforeach
                </div></div>
            </div>
        </section>
    @endif

    @if($agreements)
        @php $agreementPeak = max(1, $agreements['total']); @endphp
        <section class="panel report-panel">
            <div class="panel-heading report-panel-heading">
                <div><span class="eyebrow">MOU / PKS</span><h2>Status dokumen kerja sama</h2><p>Posisi terkini · {{ $agreements['total'] }} dokumen resmi · {{ $agreements['pklOnly'] }} mitra PKL saja</p></div>
                <a class="button button-light hide-print" href="{{ route('reports.export', ['section' => 'agreements']) }}">Ekspor Excel <span aria-hidden="true">↓</span></a>
            </div>
            <div class="agreement-status-grid">
                @foreach(\App\Models\Company::agreementStatusLabels() as $key => $label)
                    @php $count = $agreements['byStatus'][$key] ?? 0; @endphp
                    <div class="agreement-status-card"><span>{{ $label }}</span><strong>{{ $count }}</strong><span class="report-progress report-progress-amber"><i style="width: {{ round($count / $agreementPeak * 100) }}%"></i></span></div>
                @endforeach
            </div>
            <div class="report-attention">
                <div class="report-chart-heading"><div><h3>Dokumen perlu perhatian</h3><p>Segera berakhir, kedaluwarsa, atau datanya belum lengkap</p></div></div>
                <div class="table-scroll"><table><thead><tr><th>Mitra</th><th>Nomor dokumen</th><th>Berlaku sampai</th><th>Status</th></tr></thead><tbody>
                    @forelse($agreements['attention'] as $company)
                        <tr><td><strong>{{ $company->name }}</strong></td><td>{{ $company->document_number ?: '—' }}</td><td>{{ $company->valid_until?->format('d/m/Y') ?? '—' }}</td><td><span class="status-pill status-{{ $company->agreementStatus() }}">{{ \App\Models\Company::agreementStatusLabels()[$company->agreementStatus()] }}</span></td></tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state compact">Semua dokumen dalam kondisi normal.</div></td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </section>
    @endif
@endsection
