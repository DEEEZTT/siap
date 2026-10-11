@extends('layouts.app')
@section('title', 'Pratinjau '.$outgoing->subject)
@section('content')
<div class="print-toolbar hide-print">
    <a class="button button-light" href="{{ route('outgoing.show', $outgoing) }}">← Kembali</a>
    <button class="button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
<article class="letter-paper">
    @unless(in_array($outgoing->status, ['siap_dicetak', 'selesai'], true))
        <div class="draft-watermark" aria-hidden="true">DRAFT</div>
    @endunless
    <header class="letter-head">
        <img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
        <div>
            <strong>PEMERINTAH PROVINSI KEPULAUAN RIAU</strong>
            <b>DINAS PENDIDIKAN</b>
            <b>SMK NEGERI 4 TANJUNGPINANG</b>
            <small>Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125<br>Telepon (0771) 440844 · smkn4-tanjungpinang.sch.id · smkn4tpi@gmail.com</small>
        </div>
        <img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMK Negeri 4 Tanjungpinang">
    </header>
    @php
        $letterDate = ($outgoing->letter_date ?? now())->locale('id')->translatedFormat('d F Y');
    @endphp
    <div class="letter-meta">
        <div class="letter-meta-fields">
            <div>Nomor: {{ $outgoing->letter_number ?: 'Menunggu verifikasi' }}</div>
            <div>Sifat: Biasa</div>
            <div>Lampiran: {{ $outgoing->type === 'rekomendasi_mutasi' && !empty($outgoing->details['attachments']) ? 'Ada' : '—' }}</div>
            <div>Hal: {{ $outgoing->subject }}</div>
        </div>
        <div class="letter-meta-date">Tanjungpinang, {{ $letterDate }}</div>
    </div>

    @if($outgoing->type === 'undangan')
        <div class="letter-address">
            @if(!empty($outgoing->details['grade_class']))
                Yth. Bapak/Ibu orang tua/wali murid kelas {{ $outgoing->details['grade_class'] }}<br>di<br>Tanjungpinang
            @else
                Yth. {{ $outgoing->recipient_name }}<br>{{ $outgoing->recipient_address ?: 'di Tempat' }}
            @endif
        </div>
        <div class="letter-subject">Dengan hormat,</div>
        <div class="invitation-body">
        @if(!empty($outgoing->details['invitation_context']))
            <div class="letter-body invitation-opening">Sehubungan dengan {{ $outgoing->details['invitation_context'] }} bagi siswa kelas {{ $outgoing->details['grade_class'] }}, dengan ini kami mengundang Bapak/Ibu untuk hadir pada:</div>
        @else
            <div class="letter-body invitation-opening">{{ $outgoing->body ?: 'Sehubungan dengan kegiatan sekolah, kami mengundang Bapak/Ibu untuk hadir pada:' }}</div>
        @endif
        <table class="event-info"><tr><td>Hari / tanggal</td><td>: {{ $outgoing->event_date?->locale('id')->translatedFormat('l, d F Y') }}</td></tr><tr><td>Waktu</td><td>: {{ $outgoing->event_time }}</td></tr><tr><td>Tempat</td><td>: {{ $outgoing->event_place }}</td></tr></table>
        <div class="letter-body invitation-closing">Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.</div>
        </div>
    @elseif($outgoing->type === 'perintah_tugas')
        <h1 class="letter-document-title">SURAT PERINTAH TUGAS</h1>
        <div class="letter-fields letter-basis">
            <b class="letter-field-label">Dasar</b><span class="letter-field-colon">:</span><span class="letter-field-value">{{ $outgoing->details['basis'] }}</span>
        </div>
        <h2 class="letter-document-subtitle">MEMERINTAHKAN:</h2>
        <div class="letter-fields letter-details letter-task-details">
            <b class="letter-field-label">Kepada</b><span class="letter-field-colon">:</span><span class="letter-recipient-number">1.</span>
            <div class="letter-person-fields">
                <div><span>nama</span><span>: {{ $outgoing->recipient_name }}</span></div>
                @if(!empty($outgoing->details['rank']))<div><span>pangkat/golongan</span><span>: {{ $outgoing->details['rank'] }}</span></div>@endif
                @if(!empty($outgoing->details['employee_number']))<div><span>NIP/NUPTK</span><span>: {{ $outgoing->details['employee_number'] }}</span></div>@endif
                @if(!empty($outgoing->details['position']))<div><span>jabatan</span><span>: {{ $outgoing->details['position'] }}</span></div>@endif
            </div>
            <b class="letter-field-label">Untuk</b><span class="letter-field-colon">:</span><div class="letter-field-value letter-field-assignment preserve-lines">{{ $outgoing->details['assignment'] }}</div>
        </div>
        <p class="letter-closing">Demikian surat perintah tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab. Setelah selesai, yang bersangkutan agar menyampaikan laporan.</p>
    @else
        <h1 class="letter-document-title">SURAT REKOMENDASI MUTASI/PINDAH SISWA</h1>
        <p class="letter-body">Yang bertanda tangan di bawah ini:</p>
        <div class="letter-fields letter-details">
            <b class="letter-field-label">Nama</b><span class="letter-field-colon">:</span><div class="letter-field-value">Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</div>
            <b class="letter-field-label">NIP</b><span class="letter-field-colon">:</span><div class="letter-field-value">19770421 200502 2 011</div>
            <b class="letter-field-label">Pangkat/gol.</b><span class="letter-field-colon">:</span><div class="letter-field-value">Pembina Utama Muda (IV/c)</div>
            <b class="letter-field-label">Jabatan</b><span class="letter-field-colon">:</span><div class="letter-field-value">Kepala SMK Negeri 4 Tanjungpinang</div>
        </div>
        <p class="letter-body">Berdasarkan pengajuan permohonan mutasi/pindah sekolah dari {{ $outgoing->details['origin_school'] }} ke {{ $outgoing->details['destination_school'] }} dari orang tua/wali murid atas nama:</p>
        <div class="letter-fields letter-details">
            <b class="letter-field-label">Nama siswa</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->recipient_name }}</div>
            <b class="letter-field-label">Sekolah asal</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['origin_school'] }}</div>
            <b class="letter-field-label">NISN</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['nisn'] }}</div>
            <b class="letter-field-label">Kelas/semester/TP</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['class_info'] ?? '—' }}</div>
            <b class="letter-field-label">Alamat sekolah asal</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['origin_school_address'] ?? '—' }}</div>
            <b class="letter-field-label">Tempat, tgl. lahir</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['birth_place'] ?? '' }}{{ !empty($outgoing->details['birth_date']) ? ', '.\Illuminate\Support\Carbon::parse($outgoing->details['birth_date'])->locale('id')->translatedFormat('d F Y') : '' }}</div>
            @if(!empty($outgoing->details['father_name']))<b class="letter-field-label">Ayah</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['father_name'] }}</div>@endif
            @if(!empty($outgoing->details['father_occupation']))<b class="letter-field-label">Pekerjaan</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['father_occupation'] }}</div>@endif
            @if(!empty($outgoing->details['mother_name']))<b class="letter-field-label">Ibu</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['mother_name'] }}</div>@endif
            @if(!empty($outgoing->details['mother_occupation']))<b class="letter-field-label">Pekerjaan</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['mother_occupation'] }}</div>@endif
        </div>
        <p class="letter-body">Setelah melihat dan memverifikasi dokumen akademik serta alasan tujuan mutasi/pindah sekolah, dengan ini kami merekomendasikan siswa yang bersangkutan untuk mutasi/pindah ke {{ $outgoing->details['destination_school'] }}.</p>
        @if(!empty($outgoing->details['reason']))<p class="letter-body">Keterangan permohonan: {{ $outgoing->details['reason'] }}</p>@endif
        <p class="letter-body">Adapun persyaratan yang harus dilengkapi:</p>
        @if(!empty($outgoing->details['attachments']))
            <ul class="mutation-documents">@foreach(preg_split('/\r\n|\r|\n/', $outgoing->details['attachments']) as $attachment)@if(trim($attachment) !== '')<li>{{ trim($attachment) }}</li>@endif @endforeach</ul>
        @endif
        <p class="letter-closing">Demikian surat rekomendasi ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.</p>
    @endif

    <div class="signature">
        Kepala Sekolah,
        <b>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</b>
        @if($outgoing->type === 'rekomendasi_mutasi')
            Pembina Utama Muda (IV/c)
        @else
            Pembina Tk. I
        @endif
        <br>NIP 19770421 200502 2 011
    </div>
</article>
@endsection
