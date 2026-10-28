@extends('layouts.app')
@section('title', 'Rincian surat keluar')
@section('content')
<div class="page-heading outgoing-page-heading">
    <div><span class="eyebrow blue">{{ $outgoing->agenda_number ?? 'SURAT KELUAR' }}</span><h1>{{ $outgoing->subject }}</h1><p>Surat untuk {{ $outgoing->recipient_name }}</p></div>
    <a class="button button-light" href="{{ route('outgoing.index') }}">← Kembali ke agenda</a>
</div>
<div class="detail-grid record-detail-grid">
    <section class="detail-card record-detail-card">
        <span class="detail-card-kicker">RINGKASAN DOKUMEN</span>
        <h2>Isi surat</h2>
        <dl class="detail-list">
            <dt>Jenis surat</dt><dd>{{ str_replace('_', ' ', $outgoing->type) }}</dd>
            <dt>Nomor agenda</dt><dd>{{ $outgoing->agenda_number ?? 'Belum diberikan' }}</dd>
            <dt>Nomor surat</dt><dd>{{ $outgoing->letter_number ?? 'Menunggu verifikasi' }}</dd>
            <dt>Tujuan</dt><dd>{{ $outgoing->recipient_name }}</dd>
            <dt>Alamat</dt><dd>{{ $outgoing->recipient_address ?: '—' }}</dd>
            <dt>Perihal</dt><dd>{{ $outgoing->subject }}</dd>
            @if($outgoing->type === 'undangan')
                <dt>Tanggal kegiatan</dt><dd>{{ $outgoing->event_date?->format('d/m/Y') }}</dd>
                <dt>Waktu dan tempat</dt><dd>{{ $outgoing->event_time }} · {{ $outgoing->event_place }}</dd>
            @elseif($outgoing->type === 'perintah_tugas')
                <dt>Dasar tugas</dt><dd>{{ $outgoing->details['basis'] ?? '—' }}</dd>
                <dt>Pangkat / golongan</dt><dd>{{ $outgoing->details['rank'] ?? '—' }}</dd>
                <dt>NIP / NUPTK</dt><dd>{{ $outgoing->details['employee_number'] ?? '—' }}</dd>
                <dt>Jabatan</dt><dd>{{ $outgoing->details['position'] ?? '—' }}</dd>
                <dt>Rincian tugas</dt><dd class="preserve-lines">{{ $outgoing->details['assignment'] ?? '—' }}</dd>
            @else
                <dt>NISN</dt><dd>{{ $outgoing->details['nisn'] ?? '—' }}</dd>
                <dt>Sekolah asal</dt><dd>{{ $outgoing->details['origin_school'] ?? '—' }}</dd>
                <dt>Sekolah tujuan</dt><dd>{{ $outgoing->details['destination_school'] ?? '—' }}</dd>
                @if(!empty($outgoing->details['student_profile']))
                    <dt>Data siswa lainnya</dt><dd class="preserve-lines">{{ $outgoing->details['student_profile'] }}</dd>
                @endif
                <dt>Alasan</dt><dd class="preserve-lines">{{ $outgoing->details['reason'] ?? '—' }}</dd>
                @if(!empty($outgoing->details['attachments']))
                    <dt>Dokumen pendukung</dt><dd class="preserve-lines">{{ $outgoing->details['attachments'] }}</dd>
                @endif
            @endif
            <dt>Keterangan</dt><dd>{{ $outgoing->notes ?: '—' }}</dd>
            <dt>Status</dt><dd><span class="status status-{{ $outgoing->status }}">{{ str_replace('_', ' ', $outgoing->status) }}</span></dd>
            @if($outgoing->verification_note)
                <dt>Catatan Kepala TU</dt><dd>{{ $outgoing->verification_note }}</dd>
            @endif
            @if($outgoing->kepsek_note)
                <dt>Catatan Kepsek</dt><dd>{{ $outgoing->kepsek_note }}</dd>
            @endif
            @if($outgoing->kepsek_reviewed_at)
                <dt>Keputusan Kepsek</dt><dd>{{ $outgoing->principalReviewer?->name }} · {{ $outgoing->kepsek_reviewed_at->format('d/m/Y H:i') }}</dd>
            @endif
        </dl>
        <div class="document-actions">
            <a class="button" href="{{ route('outgoing.print', $outgoing) }}">Pratinjau / cetak / simpan PDF</a>
            @if($outgoing->disposition)
                <a class="button button-light" href="{{ route('outgoing.disposition.print', $outgoing) }}">Cetak disposisi lama</a>
            @endif
            @if(in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek'], true) && auth()->user()->role === 'tu')
                <a class="button button-light" href="{{ route('outgoing.edit', $outgoing) }}">Ubah surat</a>
            @endif
        </div>
        @if(in_array($outgoing->status, ['draft', 'perlu_revisi', 'perlu_revisi_kepsek', 'selesai'], true) && in_array(auth()->user()->role, ['tu', 'kepala_tu'], true) && (auth()->user()->role === 'kepala_tu' || $outgoing->created_by === auth()->id()))
            <div class="danger-zone">
                <p class="field-hint">Penghapusan surat selesai bersifat permanen dan lampiran terkait ikut dihapus.</p>
                <form method="post" action="{{ route('outgoing.destroy', $outgoing) }}" onsubmit="return confirm('Hapus surat keluar ini? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('delete')
                    <button class="button button-danger" type="submit">{{ $outgoing->status === 'selesai' ? 'Hapus surat selesai' : 'Hapus draft surat' }}</button>
                </form>
            </div>
        @endif
    </section>

    <section class="detail-card record-detail-card record-action-card">
        <span class="detail-card-kicker">TINDAKAN BERIKUTNYA</span>
        @if($outgoing->status === 'menunggu_verifikasi' && auth()->user()->role === 'kepala_tu')
            <h2>Verifikasi Kepala TU</h2>
            <form method="post" action="{{ route('outgoing.verify', $outgoing) }}">
                @csrf
                <div class="field"><label for="verification-note">Catatan untuk TU (wajib jika revisi)</label><textarea class="textarea" id="verification-note" name="verification_note">{{ old('verification_note') }}</textarea></div>
                <div class="form-actions"><button class="button button-danger" name="decision" value="revisi">Minta revisi</button><button class="button" name="decision" value="setuju">Lanjutkan ke Kepsek</button></div>
            </form>
        @elseif($outgoing->status === 'menunggu_persetujuan_kepsek' && auth()->user()->role === 'kepsek')
            <h2>Persetujuan Kepala Sekolah</h2>
            <p class="muted">Periksa isi surat. Setelah disetujui, surat dapat dicetak.</p>
            <form method="post" action="{{ route('outgoing.principal_review', $outgoing) }}">
                @csrf
                <div class="field"><label for="kepsek-note">Catatan Kepsek (wajib jika meminta revisi)</label><textarea class="textarea" id="kepsek-note" name="kepsek_note">{{ old('kepsek_note') }}</textarea></div>
                <div class="form-actions"><button class="button button-danger" name="decision" value="revisi">Minta revisi</button><button class="button" name="decision" value="setuju">Setujui dan siapkan cetak</button></div>
            </form>
        @elseif(in_array($outgoing->status, ['perlu_revisi', 'perlu_revisi_kepsek'], true))
            <h2>Perlu revisi</h2>
            <p class="muted">Surat dikembalikan ke Tata Usaha. Perbaiki catatan yang diberikan, lalu kirim kembali untuk verifikasi.</p>
        @elseif($outgoing->status === 'menunggu_verifikasi')
            <h2>Proses surat</h2><p class="muted">Menunggu pemeriksaan Kepala TU.</p>
        @elseif($outgoing->status === 'menunggu_persetujuan_kepsek')
            <h2>Proses surat</h2><p class="muted">Menunggu keputusan Kepala Sekolah.</p>
        @elseif($outgoing->disposition)
            <h2>Disposisi lama</h2>
            <dl class="detail-list"><dt>Diteruskan kepada</dt><dd>{{ implode(', ', $outgoing->disposition->recipients ?? []) }}</dd><dt>Instruksi</dt><dd>{{ implode(', ', $outgoing->disposition->instructions ?? []) }} {{ $outgoing->disposition->other_instruction }}</dd><dt>Catatan</dt><dd>{{ $outgoing->disposition->note ?: '—' }}</dd></dl>
        @else
            <h2>Proses surat</h2><p class="muted">{{ $outgoing->status === 'draft' ? 'Draft dapat diedit sebelum dikirim untuk verifikasi.' : 'Surat disetujui dan siap dicetak.' }}</p>
        @endif
    </section>
</div>
@endsection
