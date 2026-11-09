@php
    $pdfMode = $pdfMode ?? false;
    $breakLongPdfTokens = static function ($value) use ($pdfMode) {
        $text = (string) $value;
        if (! $pdfMode) {
            return $text;
        }

        return preg_replace_callback('/\S{60}/u', static fn (array $match) => $match[0]."\n", $text) ?? $text;
    };
@endphp
<article class="letter-paper {{ $outgoing->type === 'undangan' ? 'letter-paper-invitation' : '' }} {{ $outgoing->type === 'rekomendasi_mutasi' ? 'letter-paper-compact' : '' }} {{ $outgoing->type === 'perintah_tugas' ? 'letter-paper-task' : '' }}" data-print-title="Surat Keluar {{ \Illuminate\Support\Str::slug($outgoing->agenda_number ?? 'draft') }} - {{ $outgoing->subject }}">
    @unless(in_array($outgoing->status, ['siap_dicetak', 'selesai'], true))
        <div class="draft-watermark" aria-hidden="true">DRAFT</div>
    @endunless
    <header class="letter-head"><table class="letterhead-table"><tbody><tr><td width="16%" class="letterhead-logo">
        <img src="{{ $pdfMode ? $logoKepri : asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
        </td><td width="68%" class="letterhead-center"><div>
            <strong>PEMERINTAH PROVINSI KEPULAUAN RIAU</strong>
            <b>DINAS PENDIDIKAN</b>
            <b>SMK NEGERI 4 TANJUNGPINANG</b>
            <small>Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125<br>Telepon (0771) 440844 · smkn4-tanjungpinang.sch.id · smkn4tpi@gmail.com</small>
        </div></td><td width="16%" class="letterhead-logo">
        <img src="{{ $pdfMode ? $logoSmkn4 : asset('images/smkn4.png') }}" alt="Lambang SMK Negeri 4 Tanjungpinang">
        </td></tr></tbody></table></header>
    @php
        $letterDate = ($outgoing->letter_date ?? now())->locale('id')->translatedFormat('d F Y');
        $letterAttachments = trim((string) ($outgoing->details['attachments'] ?? ''));
        if ($letterAttachments === '' || in_array($letterAttachments, ['—', '–'], true)) {
            $letterAttachments = '-';
        }
    @endphp
    @if(!in_array($outgoing->type, ['rekomendasi_mutasi', 'perintah_tugas'], true))
        <div class="letter-meta">
            <div class="letter-meta-fields"><table class="letter-meta-table"><tbody>
                <tr><td>Nomor</td><td>:</td><td>{{ $outgoing->letter_number ?: 'Menunggu verifikasi' }}</td></tr>
                <tr><td>Sifat</td><td>:</td><td>Biasa</td></tr>
                <tr><td>Lampiran</td><td>:</td><td>{{ $letterAttachments }}</td></tr>
                <tr><td>Hal</td><td>:</td><td>{{ $outgoing->subject }}</td></tr>
            </tbody></table></div>
            @if($outgoing->type === 'undangan')
                <div class="letter-meta-date">Tanjungpinang, {{ $letterDate }}</div>
            @endif
        </div>
    @endif

    @if($outgoing->type === 'undangan')
        <div class="letter-address">
            @if(!empty($outgoing->details['grade_class']))
                Yth. Bapak/Ibu orang tua/wali murid kelas {{ $outgoing->details['grade_class'] }}<br>di Tanjungpinang
            @else
                Yth. {{ $outgoing->recipient_name }}<br>{{ $outgoing->recipient_address ?: 'di Tempat' }}
            @endif
        </div>
        <div class="invitation-body">
        @if(!empty($outgoing->details['invitation_context']))
            <div class="letter-body invitation-opening">Sehubungan {{ $outgoing->details['invitation_context'] }} bagi siswa kelas {{ $outgoing->details['grade_class'] }}, dengan ini kami mengundang Bapak/Ibu untuk pengambilan rapor ananda pada:</div>
        @else
            <div class="letter-body invitation-opening">{{ $outgoing->body ?: 'Sehubungan dengan kegiatan sekolah, kami mengundang Bapak/Ibu untuk pengambilan rapor ananda pada:' }}</div>
        @endif
        <table class="event-info"><tr><td>Hari, tanggal</td><td>: {{ $outgoing->event_date?->locale('id')->translatedFormat('l, d F Y') }}</td></tr><tr><td>Pukul</td><td>: {{ $outgoing->event_time }}</td></tr><tr><td>Tempat</td><td>: {{ $outgoing->event_place }}</td></tr></table>
        <div class="letter-body invitation-closing">Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.</div>
        </div>
    @elseif($outgoing->type === 'perintah_tugas')
        @php
            $taskParticipants = collect($outgoing->details['participants'] ?? [])
                ->filter(fn ($participant) => is_array($participant) && filled($participant['name'] ?? null))
                ->values();
            $taskRecipients = collect($outgoing->details['recipients'] ?? [])
                ->filter(fn ($recipient) => is_array($recipient) && filled($recipient['name'] ?? null))
                ->values();
            if ($taskRecipients->isEmpty()) {
                $taskRecipients = collect([[
                    'name' => $outgoing->recipient_name,
                    'rank' => $outgoing->details['rank'] ?? '',
                    'employee_number' => $outgoing->details['employee_number'] ?? '',
                    'position' => $outgoing->details['position'] ?? '',
                ]]);
            }
        @endphp
        <h1 class="letter-document-title">SURAT PERINTAH TUGAS</h1>
        <div class="task-letter-number">NOMOR: {{ $outgoing->letter_number ?: 'Menunggu verifikasi' }}</div>
        <table class="task-letter-basis"><tbody><tr><td width="22%">Dasar</td><td width="3%">:</td><td width="75%">{{ $breakLongPdfTokens($outgoing->details['basis']) }}</td></tr></tbody></table>
        <h2 class="letter-document-subtitle">MEMERINTAHKAN:</h2>
        <table class="letter-task-layout">
            <tbody>
            @foreach($taskRecipients as $recipient)
                <tr class="task-recipient-row"><td width="22%">{{ $loop->first ? 'Kepada' : '' }}</td><td width="3%">{{ $loop->first ? ':' : '' }}</td><td width="3%">{{ $loop->iteration }}.</td><td width="20%">nama</td><td width="3%">:</td><td width="49%">{{ $recipient['name'] }}</td></tr>
                @if(!empty($recipient['rank']))<tr class="task-recipient-row"><td></td><td></td><td></td><td>pangkat/golongan</td><td>:</td><td>{{ $recipient['rank'] }}</td></tr>@endif
                @if(!empty($recipient['employee_number']))<tr class="task-recipient-row"><td></td><td></td><td></td><td>NIP/NUPTK</td><td>:</td><td>{{ $recipient['employee_number'] }}</td></tr>@endif
                @if(!empty($recipient['position']))<tr class="task-recipient-row"><td></td><td></td><td></td><td>jabatan</td><td>:</td><td>{{ $recipient['position'] }}</td></tr>@endif
            @endforeach
            </tbody>
            <tbody><tr><td width="22%">Untuk</td><td width="3%">:</td><td colspan="4"><div class="task-assignment-text">{{ $breakLongPdfTokens($outgoing->details['assignment']) }}</div>@if($taskParticipants->isNotEmpty())<table class="task-participant-table" width="100%"><thead><tr><th width="7%">No</th><th width="46%">Nama</th><th width="47%">Kelas</th></tr></thead><tbody>@foreach($taskParticipants as $participant)<tr><td>{{ $loop->iteration }}.</td><td>{{ $participant['name'] }}</td><td>{{ $participant['class'] ?? '' }}</td></tr>@endforeach</tbody></table>@endif</td></tr></tbody>
        </table>
        <p class="letter-closing">Tugas ini harap dilaksanakan dengan penuh tanggung jawab dan setelah selesai membuat laporan.</p>
    @else
        <h1 class="letter-document-title">SURAT REKOMENDASI MUTASI/PINDAH SISWA</h1>
        <div class="mutation-letter-number">Nomor: {{ $outgoing->letter_number ?: 'Menunggu verifikasi' }}</div>
        <p class="letter-body">Yang bertanda tangan di bawah ini:</p>
        @if($pdfMode)
        <table class="letter-fields-pdf"><colgroup><col width="20%"><col width="3%"><col width="77%"></colgroup><tbody>
            <tr><td width="20%">Nama</td><td width="3%">:</td><td width="77%">Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</td></tr>
            <tr><td width="20%">NIP</td><td width="3%">:</td><td width="77%">19770421 200502 2 011</td></tr>
            <tr><td width="20%">Pangkat/gol.</td><td width="3%">:</td><td width="77%">Pembina Utama Muda (IV/c)</td></tr>
            <tr><td width="20%">Jabatan</td><td width="3%">:</td><td width="77%">Kepala SMK Negeri 4 Tanjungpinang</td></tr>
        </tbody></table>
        @else
        <div class="letter-fields letter-details">
            <b class="letter-field-label">Nama</b><span class="letter-field-colon">:</span><div class="letter-field-value">Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</div>
            <b class="letter-field-label">NIP</b><span class="letter-field-colon">:</span><div class="letter-field-value">19770421 200502 2 011</div>
            <b class="letter-field-label">Pangkat/gol.</b><span class="letter-field-colon">:</span><div class="letter-field-value">Pembina Utama Muda (IV/c)</div>
            <b class="letter-field-label">Jabatan</b><span class="letter-field-colon">:</span><div class="letter-field-value">Kepala SMK Negeri 4 Tanjungpinang</div>
        </div>
        @endif
        <p class="letter-body">Berdasarkan pengajuan permohonan mutasi/pindah sekolah dari {{ $outgoing->details['origin_school'] }} ke {{ $outgoing->details['destination_school'] }} dari orang tua/wali murid atas nama:</p>
        @if($pdfMode)
        <table class="letter-fields-pdf"><colgroup><col width="20%"><col width="3%"><col width="77%"></colgroup><tbody>
            <tr><td width="20%">Nama siswa</td><td width="3%">:</td><td width="77%">{{ $outgoing->recipient_name }}</td></tr>
            <tr><td width="20%">Sekolah asal</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['origin_school'] }}</td></tr>
            <tr><td width="20%">NISN</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['nisn'] }}</td></tr>
            <tr><td width="20%">Kelas/semester/TP</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['class_info'] ?? '—' }}</td></tr>
            <tr><td width="20%">Alamat sekolah asal</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['origin_school_address'] ?? '—' }}</td></tr>
            <tr><td width="20%">Tempat, tgl. lahir</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['birth_place'] ?? '' }}{{ !empty($outgoing->details['birth_date']) ? ', '.\Illuminate\Support\Carbon::parse($outgoing->details['birth_date'])->locale('id')->translatedFormat('d F Y') : '' }}</td></tr>
            @if(!empty($outgoing->details['father_name']) || !empty($outgoing->details['father_occupation']) || !empty($outgoing->details['mother_name']) || !empty($outgoing->details['mother_occupation']))<tr class="letter-field-heading"><td colspan="3">Nama orangtua</td></tr>@endif
            @if(!empty($outgoing->details['father_name']))<tr><td width="20%">Ayah</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['father_name'] }}</td></tr>@endif
            @if(!empty($outgoing->details['father_occupation']))<tr><td width="20%">Pekerjaan</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['father_occupation'] }}</td></tr>@endif
            @if(!empty($outgoing->details['mother_name']))<tr><td width="20%">Ibu</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['mother_name'] }}</td></tr>@endif
            @if(!empty($outgoing->details['mother_occupation']))<tr><td width="20%">Pekerjaan</td><td width="3%">:</td><td width="77%">{{ $outgoing->details['mother_occupation'] }}</td></tr>@endif
        </tbody></table>
        @else
        <div class="letter-fields letter-details">
            <b class="letter-field-label">Nama siswa</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->recipient_name }}</div>
            <b class="letter-field-label">Sekolah asal</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['origin_school'] }}</div>
            <b class="letter-field-label">NISN</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['nisn'] }}</div>
            <b class="letter-field-label">Kelas/semester/TP</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['class_info'] ?? '—' }}</div>
            <b class="letter-field-label">Alamat sekolah asal</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['origin_school_address'] ?? '—' }}</div>
            <b class="letter-field-label">Tempat, tgl. lahir</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['birth_place'] ?? '' }}{{ !empty($outgoing->details['birth_date']) ? ', '.\Illuminate\Support\Carbon::parse($outgoing->details['birth_date'])->locale('id')->translatedFormat('d F Y') : '' }}</div>
        </div>
        @if(!empty($outgoing->details['father_name']) || !empty($outgoing->details['father_occupation']) || !empty($outgoing->details['mother_name']) || !empty($outgoing->details['mother_occupation']))
        <div class="letter-fields letter-details mutation-parent-heading"><b class="letter-field-label">Nama orangtua</b><span></span><div></div></div>
        <div class="letter-fields letter-details">
            @if(!empty($outgoing->details['father_name']))<b class="letter-field-label">Ayah</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['father_name'] }}</div>@endif
            @if(!empty($outgoing->details['father_occupation']))<b class="letter-field-label">Pekerjaan</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['father_occupation'] }}</div>@endif
            @if(!empty($outgoing->details['mother_name']))<b class="letter-field-label">Ibu</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['mother_name'] }}</div>@endif
            @if(!empty($outgoing->details['mother_occupation']))<b class="letter-field-label">Pekerjaan</b><span class="letter-field-colon">:</span><div class="letter-field-value">{{ $outgoing->details['mother_occupation'] }}</div>@endif
        </div>
        @endif
        @endif
        <p class="letter-body">Setelah melihat dan memverifikasi dokumen akademik serta alasan tujuan mutasi/pindah sekolah, dengan ini kami merekomendasikan siswa yang bersangkutan untuk mutasi/pindah ke {{ $outgoing->details['destination_school'] }} dengan melengkapi:</p>
        @if(!empty($outgoing->details['attachments']))
            <ul class="mutation-documents">@foreach(preg_split('/\r\n|\r|\n/', $outgoing->details['attachments']) as $attachment)@if(trim($attachment) !== '')<li>{{ trim($attachment) }}</li>@endif @endforeach</ul>
        @endif
        <p class="letter-body mutation-follow-up">dan hal lainnya sehubungan dengan kepindahan siswa yang bersangkutan kami serahkan ke dinas terkait.</p>
        <p class="letter-closing">Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>
    @endif

    <div class="outgoing-signature-block {{ $outgoing->type === 'undangan' ? 'invitation-signature' : '' }}">
        @if($outgoing->type === 'rekomendasi_mutasi')
            <div class="mutation-signature-date">Tanjungpinang, {{ $letterDate }}</div>
        @elseif($outgoing->type === 'perintah_tugas')
            <div class="task-signature-meta">
                <div><span>Ditetapkan di</span><span>:</span><span>Tanjungpinang</span></div>
                <div><span>Pada tanggal</span><span>:</span><span>{{ $letterDate }}</span></div>
            </div>
        @endif
        <div class="signature">
            Kepala Sekolah,
            <span class="signature-space" aria-hidden="true"></span>
            <b>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</b>
            {{ $outgoing->type === 'rekomendasi_mutasi' ? 'Pembina Utama Muda (IV/c)' : 'Pembina Tk. I' }}
            <br>NIP 19770421 200502 2 011
        </div>
    </div>
</article>
