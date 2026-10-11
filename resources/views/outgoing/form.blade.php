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
<form class="form-card" method="post" action="{{ isset($outgoing) ? route('outgoing.update', $outgoing) : route('outgoing.store') }}">
    @csrf
    @if(isset($outgoing))
        @method('put')
    @else
        <input type="hidden" name="type" value="{{ $type }}">
    @endif
    <div class="form-grid">
        @if($type === 'undangan')
            <div class="field full"><label for="invitation-context">Isi setelah “Sehubungan dengan” <span class="required">*</span></label><textarea class="textarea" id="invitation-context" name="details[invitation_context]" placeholder="Contoh: telah dilaksanakannya Penilaian Akhir Semester (PAS) Ganjil Tahun Pelajaran 2025/2026" required>{{ old('details.invitation_context', $outgoing->details['invitation_context'] ?? '') }}</textarea></div>
            <div class="field"><label for="invitation-class">Kelas <span class="required">*</span></label><input class="input" id="invitation-class" name="details[grade_class]" placeholder="Contoh: X" value="{{ old('details.grade_class', $outgoing->details['grade_class'] ?? '') }}" required></div>
            <div class="field"><label for="event-date">Tanggal kegiatan <span class="required">*</span></label><input class="input" id="event-date" type="date" name="event_date" value="{{ old('event_date', isset($outgoing) && $outgoing->event_date ? $outgoing->event_date->format('Y-m-d') : '') }}" required></div>
            <div class="field"><label for="event-time">Waktu kegiatan <span class="required">*</span></label><input class="input" id="event-time" name="event_time" placeholder="08.00 WIB" value="{{ old('event_time', $outgoing->event_time ?? '') }}" required></div>
            <div class="field"><label for="event-place">Tempat kegiatan <span class="required">*</span></label><input class="input" id="event-place" name="event_place" value="{{ old('event_place', $outgoing->event_place ?? '') }}" required></div>
        @elseif($type === 'perintah_tugas')
            <div class="field"><label for="employee-name">Nama pegawai yang ditugaskan <span class="required">*</span></label><input class="input" id="employee-name" name="recipient_name" value="{{ old('recipient_name', $outgoing->recipient_name ?? '') }}" required></div>
            <div class="field"><label for="task-subject">Perihal <span class="required">*</span></label><input class="input" id="task-subject" name="subject" value="{{ old('subject', $outgoing->subject ?? $defaultSubject) }}" required></div>
            <div class="field"><label for="employee-rank">Pangkat / golongan</label><input class="input" id="employee-rank" name="details[rank]" value="{{ old('details.rank', $outgoing->details['rank'] ?? '') }}"></div>
            <div class="field"><label for="employee-number">NIP / NUPTK</label><input class="input" id="employee-number" name="details[employee_number]" value="{{ old('details.employee_number', $outgoing->details['employee_number'] ?? '') }}"></div>
            <div class="field full"><label for="employee-position">Jabatan</label><input class="input" id="employee-position" name="details[position]" value="{{ old('details.position', $outgoing->details['position'] ?? '') }}"></div>
            <div class="field full"><label for="task-basis">Dasar penugasan <span class="required">*</span></label><textarea class="textarea" id="task-basis" name="details[basis]" required>{{ old('details.basis', $outgoing->details['basis'] ?? '') }}</textarea></div>
            <div class="field full"><label for="task-assignment">Rincian tugas <span class="required">*</span></label><textarea class="textarea" id="task-assignment" name="details[assignment]" placeholder="Tugas, tanggal/kegiatan, tempat, dan peserta jika ada" required>{{ old('details.assignment', $outgoing->details['assignment'] ?? '') }}</textarea></div>
        @else
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
            <div class="field"><label for="father-name">Nama ayah / wali</label><input class="input" id="father-name" name="details[father_name]" value="{{ old('details.father_name', $outgoing->details['father_name'] ?? '') }}"></div>
            <div class="field"><label for="father-occupation">Pekerjaan ayah / wali</label><input class="input" id="father-occupation" name="details[father_occupation]" value="{{ old('details.father_occupation', $outgoing->details['father_occupation'] ?? '') }}"></div>
            <div class="field"><label for="mother-name">Nama ibu</label><input class="input" id="mother-name" name="details[mother_name]" value="{{ old('details.mother_name', $outgoing->details['mother_name'] ?? '') }}"></div>
            <div class="field"><label for="mother-occupation">Pekerjaan ibu</label><input class="input" id="mother-occupation" name="details[mother_occupation]" value="{{ old('details.mother_occupation', $outgoing->details['mother_occupation'] ?? '') }}"></div>
            <div class="field full"><label for="mutation-reason">Catatan permohonan orang tua (opsional)</label><textarea class="textarea" id="mutation-reason" name="details[reason]" placeholder="Isi bila ada alasan khusus">{{ old('details.reason', $outgoing->details['reason'] ?? '') }}</textarea></div>
            <div class="field full"><label for="mutation-attachments">Dokumen yang diserahkan</label><textarea class="textarea" id="mutation-attachments" name="details[attachments]" placeholder="Satu dokumen per baris">{{ old('details.attachments', $outgoing->details['attachments'] ?? "Surat pindah Dapodik\nFotokopi ijazah SLTP\nFotokopi Kartu Keluarga\nFotokopi Akte Kelahiran") }}</textarea></div>
        @endif
        @if($type !== 'undangan')
            <div class="field full"><label for="letter-notes">Catatan buku agenda</label><input class="input" id="letter-notes" name="notes" value="{{ old('notes', $outgoing->notes ?? '') }}" placeholder="Catatan tambahan (opsional)"></div>
        @endif
    </div>
    <div class="form-actions">
        <a class="button button-light" href="{{ isset($outgoing) ? route('outgoing.show', $outgoing) : route('outgoing.create') }}">Batal</a>
        <button class="button button-light" name="action" value="draft">Simpan draft</button>
        <button class="button" name="action" value="submit">Kirim untuk verifikasi</button>
    </div>
</form>
@if($type === 'rekomendasi_mutasi')
    @push('scripts')
        <script>
            document.getElementById('student-record')?.addEventListener('change', (event) => {
                const option = event.currentTarget.selectedOptions[0];
                if (!option?.dataset.studentName) return;

                document.getElementById('student-name').value = option.dataset.studentName;
                document.getElementById('birth-place').value = option.dataset.birthPlace ?? '';
                document.getElementById('birth-date').value = option.dataset.birthDate ?? '';
            });
        </script>
    @endpush
@endif
@endsection
