@extends('layouts.app')
@section('title', isset($outgoing) ? 'Ubah surat keluar' : 'Buat '.$typeLabel)
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow blue">SURAT KELUAR · {{ strtoupper($typeLabel) }}</span>
        <h1>{{ isset($outgoing) ? 'Ubah '.$typeLabel : 'Buat '.$typeLabel }}</h1>
        <p>Isi data utama surat. Nomor surat dibuat setelah surat diverifikasi Kepala TU.</p>
    </div>
</div>
@if(isset($outgoing) && $outgoing->kepsek_note)
    <div class="alert alert-error"><b>Catatan revisi Kepala Sekolah</b><p>{{ $outgoing->kepsek_note }}</p></div>
@elseif(isset($outgoing) && $outgoing->verification_note)
    <div class="alert alert-error"><b>Catatan revisi Kepala TU</b><p>{{ $outgoing->verification_note }}</p></div>
@endif
<div class="outgoing-form-preview-layout {{ $type === 'rekomendasi_mutasi' ? 'outgoing-form-preview-mutation' : '' }}">
<form class="form-card letter-form-card outgoing-letter-form outgoing-letter-form-{{ $type }}" method="post" action="{{ isset($outgoing) ? route('outgoing.update', $outgoing) : route('outgoing.store') }}">
    @csrf
    @if(isset($outgoing))
        @method('put')
    @else
        <input type="hidden" name="type" value="{{ $type }}">
    @endif
    <div class="letter-form-intro"><span class="letter-form-icon" aria-hidden="true">&#8593;</span><div><h2>{{ isset($outgoing) ? 'Perbarui surat' : 'Lengkapi isi surat' }}</h2><p>Pastikan nama, tanggal, dan rincian tugas sudah benar sebelum dikirim.</p></div><span class="letter-form-tag">{{ strtoupper($typeLabel) }}</span></div>
    <div class="form-grid">
        @if($type === 'undangan')
            <div class="field full letter-form-section"><span>Informasi undangan</span></div>
            <div class="field full"><label for="invitation-context">Isi setelah “Sehubungan” <span class="required">*</span></label><textarea class="textarea" id="invitation-context" name="details[invitation_context]" placeholder="Contoh: telah dilaksanakannya Penilaian Akhir Semester (PAS) Ganjil Tahun Pelajaran 2025/2026" required>{{ old('details.invitation_context', $outgoing->details['invitation_context'] ?? '') }}</textarea></div>
            <div class="field"><label for="invitation-class">Kelas <span class="required">*</span></label><input class="input" id="invitation-class" name="details[grade_class]" placeholder="Contoh: X" value="{{ old('details.grade_class', $outgoing->details['grade_class'] ?? '') }}" required></div>
            <div class="field"><label for="event-date">Tanggal kegiatan <span class="required">*</span></label><input class="input" id="event-date" type="date" name="event_date" value="{{ old('event_date', isset($outgoing) && $outgoing->event_date ? $outgoing->event_date->format('Y-m-d') : '') }}" required></div>
            <div class="field"><label for="event-time">Waktu kegiatan <span class="required">*</span></label><input class="input" id="event-time" name="event_time" placeholder="08.00 WIB" value="{{ old('event_time', $outgoing->event_time ?? '') }}" required></div>
            <div class="field"><label for="event-place">Tempat kegiatan <span class="required">*</span></label><input class="input" id="event-place" name="event_place" value="{{ old('event_place', $outgoing->event_place ?? '') }}" required></div>
            <div class="field full"><label for="invitation-attachments">Lampiran <span class="muted">(opsional)</span></label><input class="input" id="invitation-attachments" name="details[attachments]" value="{{ old('details.attachments', $outgoing->details['attachments'] ?? '—') }}" placeholder="—"><small class="field-hint">Isi nama atau jumlah berkas yang ikut dikirim. Biarkan “—” jika tidak ada.</small></div>
        @elseif($type === 'perintah_tugas')
            @php
                $oldAdditionalTaskRecipients = old('details.recipients');
                $storedTaskRecipients = $outgoing->details['recipients'] ?? [];
                $storedTaskRecipients = is_array($storedTaskRecipients) ? array_values($storedTaskRecipients) : [];
                $taskRecipients = is_array($oldAdditionalTaskRecipients) ? $oldAdditionalTaskRecipients : $storedTaskRecipients;
                $primaryTaskRecipient = is_array($oldAdditionalTaskRecipients) ? [
                    'name' => old('recipient_name', ''),
                    'rank' => old('details.rank', ''),
                    'employee_number' => old('details.employee_number', ''),
                    'position' => old('details.position', ''),
                ] : ($storedTaskRecipients[0] ?? []);
                if (!$primaryTaskRecipient) {
                    $primaryTaskRecipient = [
                        'name' => $outgoing->recipient_name ?? '',
                        'rank' => $outgoing->details['rank'] ?? '',
                        'employee_number' => $outgoing->details['employee_number'] ?? '',
                        'position' => $outgoing->details['position'] ?? '',
                    ];
                }
                $additionalTaskRecipients = is_array($oldAdditionalTaskRecipients) ? $taskRecipients : array_slice($taskRecipients, 1, null, true);
                $nextTaskRecipientIndex = $additionalTaskRecipients ? max(array_map('intval', array_keys($additionalTaskRecipients))) + 1 : 1;
            @endphp
            <div class="field full letter-form-section"><span>Data pegawai</span></div>
            <div class="field"><label for="employee-name">Nama pegawai yang ditugaskan <span class="required">*</span></label><input class="input" id="employee-name" name="recipient_name" value="{{ old('recipient_name', $primaryTaskRecipient['name'] ?? '') }}" required></div>
            <div class="field"><label for="task-subject">Perihal <span class="required">*</span></label><input class="input" id="task-subject" name="subject" value="{{ old('subject', $outgoing->subject ?? $defaultSubject) }}" required></div>
            <div class="field"><label for="employee-rank">Pangkat / golongan</label><input class="input" id="employee-rank" name="details[rank]" value="{{ old('details.rank', $primaryTaskRecipient['rank'] ?? '') }}"></div>
            <div class="field"><label for="employee-number">NIP / NUPTK</label><input class="input" id="employee-number" name="details[employee_number]" value="{{ old('details.employee_number', $primaryTaskRecipient['employee_number'] ?? '') }}"></div>
            <div class="field full"><label for="employee-position">Jabatan</label><input class="input" id="employee-position" name="details[position]" value="{{ old('details.position', $primaryTaskRecipient['position'] ?? '') }}"></div>
            <div class="field full task-recipient-field">
                <span class="field-label">Penerima lainnya <span class="muted">(opsional)</span></span>
                <div class="task-recipient-inputs" data-task-recipient-list data-next-index="{{ $nextTaskRecipientIndex }}">
                    @foreach($additionalTaskRecipients as $index => $recipient)
                        <div class="task-recipient-input-row">
                            <input class="input" name="details[recipients][{{ $index }}][name]" value="{{ $recipient['name'] ?? '' }}" placeholder="Nama pegawai" aria-label="Nama pegawai penerima {{ $index + 1 }}">
                            <input class="input" name="details[recipients][{{ $index }}][rank]" value="{{ $recipient['rank'] ?? '' }}" placeholder="Pangkat / golongan" aria-label="Pangkat atau golongan penerima {{ $index + 1 }}">
                            <input class="input" name="details[recipients][{{ $index }}][employee_number]" value="{{ $recipient['employee_number'] ?? '' }}" placeholder="NIP / NUPTK" aria-label="NIP atau NUPTK penerima {{ $index + 1 }}">
                            <input class="input" name="details[recipients][{{ $index }}][position]" value="{{ $recipient['position'] ?? '' }}" placeholder="Jabatan" aria-label="Jabatan penerima {{ $index + 1 }}">
                            <button class="button button-light button-small" type="button" data-remove-task-recipient>Hapus</button>
                        </div>
                    @endforeach
                </div>
                <template id="task-recipient-template"><div class="task-recipient-input-row"><input class="input" data-task-recipient-field="name" placeholder="Nama pegawai"><input class="input" data-task-recipient-field="rank" placeholder="Pangkat / golongan"><input class="input" data-task-recipient-field="employee_number" placeholder="NIP / NUPTK"><input class="input" data-task-recipient-field="position" placeholder="Jabatan"><button class="button button-light button-small" type="button" data-remove-task-recipient>Hapus</button></div></template>
                <button class="button button-light button-small task-recipient-add" type="button" data-add-task-recipient>+ Tambah penerima</button>
                <span class="field-hint">Tambahkan pegawai lain yang menerima surat perintah tugas.</span>
            </div>
            <div class="field full letter-form-section"><span>Rincian penugasan</span></div>
            <div class="field full"><label for="task-basis">Dasar penugasan <span class="required">*</span></label><textarea class="textarea" id="task-basis" name="details[basis]" required>{{ old('details.basis', $outgoing->details['basis'] ?? '') }}</textarea></div>
            <div class="field full"><label for="task-assignment">Rincian tugas <span class="required">*</span></label><textarea class="textarea" id="task-assignment" name="details[assignment]" placeholder="Tugas, tanggal/kegiatan, tempat, dan peserta jika ada" required>{{ old('details.assignment', $outgoing->details['assignment'] ?? '') }}</textarea></div>
            <div class="field full task-participant-field">
                <label>Daftar peserta <span class="muted">(opsional)</span></label>
                @php
                    $taskParticipants = old('details.participants', $outgoing->details['participants'] ?? []);
                    $taskParticipants = is_array($taskParticipants) ? $taskParticipants : [];
                    $nextTaskParticipantIndex = $taskParticipants ? max(array_map('intval', array_keys($taskParticipants))) + 1 : 0;
                @endphp
                <div class="task-participant-inputs" data-task-participant-list data-next-index="{{ $nextTaskParticipantIndex }}">
                    @foreach($taskParticipants as $index => $participant)
                        <div class="task-participant-input-row">
                            <input class="input" name="details[participants][{{ $index }}][name]" value="{{ $participant['name'] ?? '' }}" placeholder="Nama peserta" aria-label="Nama peserta {{ $index + 1 }}">
                            <input class="input" name="details[participants][{{ $index }}][class]" value="{{ $participant['class'] ?? '' }}" placeholder="Kelas" aria-label="Kelas peserta {{ $index + 1 }}">
                            <button class="button button-light button-small" type="button" data-remove-task-participant>Hapus</button>
                        </div>
                    @endforeach
                </div>
                <template id="task-participant-template"><div class="task-participant-input-row"><input class="input" data-task-participant-name placeholder="Nama peserta"><input class="input" data-task-participant-class placeholder="Kelas"><button class="button button-light button-small" type="button" data-remove-task-participant>Hapus</button></div></template>
                <button class="button button-light button-small task-participant-add" type="button" data-add-task-participant>+ Tambah peserta</button>
                <span class="field-hint">Isi nama dan kelas untuk setiap peserta. Daftar akan diberi nomor otomatis pada surat cetak.</span>
            </div>
        @else
            <div class="field full letter-form-section"><span>Data siswa dan sekolah</span></div>
            <input type="hidden" name="subject" value="{{ $outgoing->subject ?? $defaultSubject }}">
            <div class="field full"><label for="student-record">Isi data dari daftar siswa PKL (opsional)</label><select class="select" id="student-record"><option value="">Pilih siswa untuk mengisi nama dan data lahir</option>@foreach($students as $student)<option data-student-name="{{ $student->name }}" data-birth-place="{{ $student->birth_place }}" data-birth-date="{{ $student->birth_date?->format('Y-m-d') }}">{{ $student->name }} · {{ $student->nis }}</option>@endforeach</select><small class="field-hint">NISN tetap diisi sesuai data mutasi.</small></div>
            <div class="field"><label for="student-name">Nama siswa <span class="required">*</span></label><input class="input" id="student-name" name="recipient_name" value="{{ old('recipient_name', $outgoing->recipient_name ?? '') }}" required></div>
            <div class="field"><label for="student-nisn">NISN <span class="required">*</span></label><input class="input" id="student-nisn" name="details[nisn]" value="{{ old('details.nisn', $outgoing->details['nisn'] ?? '') }}" required></div>
            <div class="field"><label for="class-info">Kelas / semester / tahun pelajaran <span class="required">*</span></label><input class="input" id="class-info" name="details[class_info]" placeholder="Contoh: X / Ganjil / 2026/2027" value="{{ old('details.class_info', $outgoing->details['class_info'] ?? '') }}" required></div>
            <div class="field"><label for="birth-place">Tempat lahir <span class="required">*</span></label><input class="input" id="birth-place" name="details[birth_place]" value="{{ old('details.birth_place', $outgoing->details['birth_place'] ?? '') }}" required></div>
            <div class="field"><label for="birth-date">Tanggal lahir <span class="required">*</span></label><input class="input" type="date" id="birth-date" name="details[birth_date]" value="{{ old('details.birth_date', $outgoing->details['birth_date'] ?? '') }}" required></div>
            <div class="field"><label for="origin-school">Sekolah asal <span class="required">*</span></label><input class="input" id="origin-school" name="details[origin_school]" value="{{ old('details.origin_school', $outgoing->details['origin_school'] ?? '') }}" required></div>
            <div class="field"><label for="destination-school">Sekolah tujuan <span class="required">*</span></label><input class="input" id="destination-school" name="details[destination_school]" value="{{ old('details.destination_school', $outgoing->details['destination_school'] ?? '') }}" required></div>
            <div class="field full"><label for="origin-school-address">Alamat sekolah asal <span class="required">*</span></label><input class="input" id="origin-school-address" name="details[origin_school_address]" value="{{ old('details.origin_school_address', $outgoing->details['origin_school_address'] ?? '') }}" required></div>
            <div class="field full letter-form-section"><span>Data orang tua / wali</span></div>
            <div class="field"><label for="father-name">Nama ayah / wali</label><input class="input" id="father-name" name="details[father_name]" value="{{ old('details.father_name', $outgoing->details['father_name'] ?? '') }}"></div>
            <div class="field"><label for="father-occupation">Pekerjaan ayah / wali</label><input class="input" id="father-occupation" name="details[father_occupation]" value="{{ old('details.father_occupation', $outgoing->details['father_occupation'] ?? '') }}"></div>
            <div class="field"><label for="mother-name">Nama ibu</label><input class="input" id="mother-name" name="details[mother_name]" value="{{ old('details.mother_name', $outgoing->details['mother_name'] ?? '') }}"></div>
            <div class="field"><label for="mother-occupation">Pekerjaan ibu</label><input class="input" id="mother-occupation" name="details[mother_occupation]" value="{{ old('details.mother_occupation', $outgoing->details['mother_occupation'] ?? '') }}"></div>
            <div class="field full"><label for="mutation-reason">Catatan permohonan orang tua (opsional)</label><textarea class="textarea" id="mutation-reason" name="details[reason]" placeholder="Isi bila ada alasan khusus">{{ old('details.reason', $outgoing->details['reason'] ?? '') }}</textarea></div>
            <div class="field full"><label for="mutation-attachments">Dokumen yang diserahkan</label><textarea class="textarea" id="mutation-attachments" name="details[attachments]" placeholder="Satu dokumen per baris">{{ old('details.attachments', $outgoing->details['attachments'] ?? "Surat pindah Dapodik\nFotokopi ijazah SLTP\nFotokopi Kartu Keluarga\nFotokopi Akte Kelahiran") }}</textarea></div>
        @endif
        @if($type !== 'undangan')
            <div class="field full letter-form-section"><span>Catatan tambahan</span></div>
            <div class="field full"><label for="letter-notes">Catatan buku agenda</label><input class="input" id="letter-notes" name="notes" value="{{ old('notes', $outgoing->notes ?? '') }}" placeholder="Catatan tambahan (opsional)"></div>
        @endif
    </div>
    <div class="form-actions">
        @if($type === 'undangan')<button class="button button-light" type="button" data-invitation-preview-open>Pratinjau surat</button>@endif
        <a class="button button-light" href="{{ isset($outgoing) ? route('outgoing.show', $outgoing) : route('outgoing.create') }}">Batal</a>
        <button class="button button-light" name="action" value="draft">Simpan draft</button>
        <button class="button" name="action" value="submit">Kirim untuk verifikasi</button>
    </div>
</form>
<aside class="letter-live-preview" aria-label="Pratinjau surat">
    <div class="letter-live-preview-heading"><div><span class="eyebrow blue">PRATINJAU LANGSUNG</span><h2>Contoh tampilan surat</h2><p>Pratinjau berubah mengikuti isian formulir.</p></div><span class="letter-preview-badge">DRAFT</span></div>
    <div class="letter-preview-viewport"><article class="letter-paper {{ $type === 'undangan' ? 'letter-paper-invitation' : '' }} {{ $type === 'rekomendasi_mutasi' ? 'letter-paper-compact' : '' }} {{ $type === 'perintah_tugas' ? 'letter-paper-task' : '' }}">
        <header class="letter-head"><img src="{{ asset('images/kepri.png') }}" alt=""><div><strong>PEMERINTAH PROVINSI KEPULAUAN RIAU</strong><b>DINAS PENDIDIKAN</b><b>SMK NEGERI 4 TANJUNGPINANG</b><small>Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125<br>Telepon (0771) 440844 · smkn4-tanjungpinang.sch.id · smkn4tpi@gmail.com</small></div><img src="{{ asset('images/smkn4.png') }}" alt=""></header>
        @if($type === 'undangan')
            <div class="letter-meta"><div class="letter-meta-fields"><table class="letter-meta-table"><tbody><tr><td>Nomor</td><td>:</td><td>Menunggu verifikasi</td></tr><tr><td>Sifat</td><td>:</td><td>Biasa</td></tr><tr><td>Lampiran</td><td>:</td><td><span data-preview-field="attachments">-</span></td></tr><tr><td>Hal</td><td>:</td><td>Undangan</td></tr></tbody></table></div><div class="letter-meta-date">Tanjungpinang, {{ now()->locale('id')->translatedFormat('d F Y') }}</div></div>
            <div class="letter-address">Yth. Bapak/Ibu orang tua/wali murid kelas <span data-preview-field="grade_class">…</span><br>di Tanjungpinang</div>
            <div class="letter-body invitation-opening">Sehubungan <span data-preview-field="invitation_context">[isi undangan]</span> bagi siswa kelas <span data-preview-field="grade_class">…</span>, dengan ini kami mengundang Bapak/Ibu untuk pengambilan rapor ananda pada:</div>
            <table class="event-info"><tr><td>Hari, tanggal</td><td>: <span data-preview-field="event_date">…</span></td></tr><tr><td>Pukul</td><td>: <span data-preview-field="event_time">…</span></td></tr><tr><td>Tempat</td><td>: <span data-preview-field="event_place">…</span></td></tr></table><div class="letter-body invitation-closing">Atas perhatian dan kehadiran Bapak/Ibu diucapkan terima kasih.</div>
            <div class="outgoing-signature-block invitation-signature"><div class="signature">Kepala Sekolah,<span class="signature-space" aria-hidden="true"></span><b>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</b>Pembina Tk. I<br>NIP 19770421 200502 2 011</div></div>
        @elseif($type === 'perintah_tugas')
            <h1 class="letter-document-title">SURAT PERINTAH TUGAS</h1><div class="task-letter-number">NOMOR: Menunggu verifikasi</div>
            <table class="task-letter-basis"><tbody><tr><td width="21%">Dasar</td><td width="3%">:</td><td width="76%" data-preview-field="basis">[dasar penugasan]</td></tr></tbody></table><h2 class="letter-document-subtitle">MEMERINTAHKAN:</h2>
            <table class="letter-task-layout"><tbody data-preview-recipients></tbody><tbody><tr><td width="22%">Untuk</td><td width="3%">:</td><td colspan="4"><span class="task-assignment-text" data-preview-field="assignment">[rincian tugas]</span><div class="task-participant-preview" data-preview-participants hidden></div></td></tr></tbody></table><p class="letter-closing">Tugas ini harap dilaksanakan dengan penuh tanggung jawab dan setelah selesai membuat laporan.</p>
        @else
            <h1 class="letter-document-title">SURAT REKOMENDASI MUTASI/PINDAH SISWA</h1><div class="mutation-letter-number">Nomor: Menunggu verifikasi</div><p class="letter-body">Yang bertanda tangan di bawah ini:</p>
            <div class="letter-fields letter-details"><b class="letter-field-label">Nama</b><span>:</span><div>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</div><b class="letter-field-label">NIP</b><span>:</span><div>19770421 200502 2 011</div><b class="letter-field-label">Pangkat/gol.</b><span>:</span><div>Pembina Utama Muda (IV/c)</div><b class="letter-field-label">Jabatan</b><span>:</span><div>Kepala SMK Negeri 4 Tanjungpinang</div></div>
            <p class="letter-body">Berdasarkan pengajuan permohonan mutasi/pindah sekolah dari <span data-preview-field="origin_school">[sekolah asal]</span> ke <span data-preview-field="destination_school">[sekolah tujuan]</span> dari orang tua/wali murid atas nama:</p>
            <div class="letter-fields letter-details"><b class="letter-field-label">Nama siswa</b><span>:</span><div data-preview-field="recipient_name">[nama siswa]</div><b class="letter-field-label">Sekolah asal</b><span>:</span><div data-preview-field="origin_school">[sekolah asal]</div><b class="letter-field-label">NISN</b><span>:</span><div data-preview-field="nisn">[NISN]</div><b class="letter-field-label">Kelas/semester/TP</b><span>:</span><div data-preview-field="class_info">—</div><b class="letter-field-label">Alamat sekolah asal</b><span>:</span><div data-preview-field="origin_school_address">—</div><b class="letter-field-label">Tempat, tgl. lahir</b><span>:</span><div><span data-preview-field="birth_place">—</span><span data-preview-field="birth_date"></span></div></div>
            <div class="letter-fields letter-details mutation-parent-heading" data-preview-row="parent-label" hidden><b class="letter-field-label">Nama orangtua</b><span></span><div></div></div>
            <div class="letter-fields letter-details" data-preview-row="father" hidden><b class="letter-field-label">Ayah</b><span>:</span><div data-preview-field="father_name"></div><b class="letter-field-label">Pekerjaan</b><span>:</span><div data-preview-field="father_occupation"></div></div>
            <div class="letter-fields letter-details" data-preview-row="mother" hidden><b class="letter-field-label">Ibu</b><span>:</span><div data-preview-field="mother_name"></div><b class="letter-field-label">Pekerjaan</b><span>:</span><div data-preview-field="mother_occupation"></div></div>
            <p class="letter-body" data-preview-row="reason" hidden><b>Catatan permohonan orang tua:</b> <span data-preview-field="reason"></span></p>
            <p class="letter-body">Dengan ini kami merekomendasikan siswa yang bersangkutan untuk mutasi/pindah ke <span data-preview-field="destination_school">[sekolah tujuan]</span>.</p>
            <div class="mutation-documents" data-preview-row="attachments" hidden><b>Dokumen yang diserahkan:</b><ol data-preview-attachments></ol></div>
            <div class="mutation-signature"><div class="mutation-signature-date">Tanjungpinang, {{ now()->locale('id')->translatedFormat('d F Y') }}</div><div class="signature">Kepala SMK Negeri 4 Tanjungpinang<b>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</b></div></div>
        @endif
    </article></div>
</aside>
</div>
@endsection
