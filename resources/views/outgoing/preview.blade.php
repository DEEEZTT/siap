<div class="modal-record-preview outgoing-modal-preview">
    @php
        $recipientDisplay = $outgoing->type === 'perintah_tugas'
            ? collect($outgoing->details['recipients'] ?? [])->pluck('name')->filter()->implode(', ')
            : '';
        $recipientDisplay = $recipientDisplay ?: $outgoing->recipient_name;
        $canManageOutgoing = in_array(auth()->user()->role, ['tu', 'kepala_tu'], true);
        $canDeleteOutgoing = $canManageOutgoing
            && in_array($outgoing->status, ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'perlu_revisi_kepsek', 'selesai'], true)
            && (auth()->user()->role === 'kepala_tu' || $outgoing->created_by === auth()->id());
    @endphp
    <div class="page-heading outgoing-page-heading">
        <div>
            <span class="eyebrow blue">{{ $outgoing->agenda_number ?? 'SURAT KELUAR' }}</span>
            <h1>{{ $outgoing->subject }}</h1>
            <p>Surat untuk {{ $recipientDisplay }}</p>
        </div>
    </div>

    <section class="detail-card record-detail-card">
        <span class="detail-card-kicker">RINGKASAN DOKUMEN</span>
        <h2>Isi surat</h2>
        <dl class="detail-list">
            <dt>Jenis surat</dt><dd>{{ ucfirst(str_replace('_', ' ', $outgoing->type)) }}</dd>
            <dt>Nomor agenda</dt><dd>{{ $outgoing->agenda_number ?? 'Belum diberikan' }}</dd>
            <dt>Nomor surat</dt><dd>{{ $outgoing->letter_number ?? 'Menunggu verifikasi' }}</dd>
            <dt>Tujuan</dt><dd>{{ $recipientDisplay }}</dd>
            <dt>Alamat</dt><dd>{{ $outgoing->recipient_address ?: '—' }}</dd>
            <dt>Perihal</dt><dd>{{ $outgoing->subject }}</dd>
            @if($outgoing->type === 'undangan')
                <dt>Kelas</dt><dd>{{ $outgoing->details['grade_class'] ?? '—' }}</dd>
                <dt>Tanggal kegiatan</dt><dd>{{ $outgoing->event_date?->format('d/m/Y') ?? '—' }}</dd>
                <dt>Waktu dan tempat</dt><dd>{{ $outgoing->event_time }} · {{ $outgoing->event_place }}</dd>
                <dt>Lampiran</dt><dd>{{ trim((string) ($outgoing->details['attachments'] ?? '')) ?: '—' }}</dd>
            @elseif($outgoing->type === 'perintah_tugas')
                <dt>Dasar tugas</dt><dd>{{ $outgoing->details['basis'] ?? '—' }}</dd>
                <dt>Jabatan</dt><dd>{{ $outgoing->details['position'] ?? '—' }}</dd>
                <dt>Rincian tugas</dt><dd class="preserve-lines">{{ $outgoing->details['assignment'] ?? '—' }}</dd>
            @else
                <dt>NISN</dt><dd>{{ $outgoing->details['nisn'] ?? '—' }}</dd>
                <dt>Sekolah asal</dt><dd>{{ $outgoing->details['origin_school'] ?? '—' }}</dd>
                <dt>Sekolah tujuan</dt><dd>{{ $outgoing->details['destination_school'] ?? '—' }}</dd>
                <dt>Alasan</dt><dd>{{ $outgoing->details['reason'] ?? '—' }}</dd>
            @endif
            <dt>Keterangan</dt><dd>{{ $outgoing->notes ?: '—' }}</dd>
            <dt>Status</dt><dd><span class="status status-{{ $outgoing->status }}">{{ $outgoing->statusLabel() }}</span></dd>
        </dl>
        <div class="document-actions">
            <a class="button button-light" href="{{ route('outgoing.print', $outgoing) }}" target="_blank" rel="noopener">Pratinjau / cetak</a>
        </div>
    </section>

    <section class="detail-card record-detail-card">
        <span class="detail-card-kicker">TINDAKAN BERIKUTNYA</span>
        @if($outgoing->status === 'menunggu_verifikasi')
            <h2>Verifikasi Kepala TU</h2>
            <p class="muted">Surat menunggu pemeriksaan Kepala TU.</p>
        @elseif($outgoing->status === 'menunggu_persetujuan_kepsek')
            <h2>Persetujuan Kepala Sekolah / Kepala TU</h2>
            <p class="muted">Surat menunggu keputusan Kepala Sekolah atau Kepala TU.</p>
        @elseif(in_array($outgoing->status, ['draft', 'perlu_revisi', 'perlu_revisi_kepsek'], true))
            <h2>{{ $outgoing->status === 'draft' ? 'Draft surat' : ($outgoing->status === 'perlu_revisi_kepsek' ? 'Dikembalikan Kepala Sekolah' : 'Surat perlu revisi') }}</h2>
            <p class="muted">{{ $outgoing->status === 'perlu_revisi_kepsek' ? 'Perbaiki isi surat sesuai catatan Kepala Sekolah, lalu kirim kembali untuk verifikasi.' : 'Surat dapat diperiksa atau diperbarui sebelum dikirim untuk verifikasi.' }}</p>
        @elseif($outgoing->status === 'siap_dicetak')
            <h2>Surat disetujui</h2>
            <p class="muted">Surat siap dicetak.</p>
        @else
            <h2>Surat selesai</h2>
            <p class="muted">Surat telah dicetak atau dikirim.</p>
        @endif
        <div class="document-actions">
            @if($outgoing->status === 'siap_dicetak' && $canManageOutgoing)
                <form class="outgoing-complete-form" method="post" action="{{ route('outgoing.complete', $outgoing) }}">
                    @csrf
                    <button class="button" type="submit">Tandai selesai</button>
                </form>
            @endif
            @if(in_array($outgoing->status, ['draft', 'perlu_revisi', 'perlu_revisi_kepsek'], true) && $canManageOutgoing)
                <a class="button button-light" href="{{ route('outgoing.edit', $outgoing) }}" data-outgoing-edit-modal>Ubah surat</a>
                <form class="outgoing-submit-form" method="post" action="{{ route('outgoing.submit', $outgoing) }}">
                    @csrf
                    <button class="button" type="submit">Kirim untuk verifikasi</button>
                </form>
            @endif
            @if($canDeleteOutgoing)
                <form class="outgoing-delete-form" method="post" action="{{ route('outgoing.destroy', $outgoing) }}">
                    @csrf
                    @method('delete')
                    <button class="button button-danger" type="submit">Hapus surat</button>
                </form>
            @endif
        </div>
        @if($outgoing->status === 'menunggu_verifikasi' && auth()->user()->role === 'kepala_tu')
            <form class="outgoing-review-form" method="post" action="{{ route('outgoing.verify', $outgoing) }}">
                @csrf
                <div class="field">
                    <label for="modal-verification-note">Catatan untuk TU (wajib jika meminta revisi)</label>
                    <textarea class="textarea" id="modal-verification-note" name="verification_note" placeholder="Tulis catatan revisi bila diperlukan"></textarea>
                </div>
                <div class="form-actions">
                    <button class="button button-danger" name="decision" value="revisi" type="submit">Minta revisi</button>
                    <button class="button" name="decision" value="setuju" type="submit">Lanjutkan ke Kepala Sekolah</button>
                </div>
            </form>
        @elseif($outgoing->status === 'menunggu_persetujuan_kepsek' && in_array(auth()->user()->role, ['kepsek', 'kepala_tu'], true))
            <form class="outgoing-review-form" method="post" action="{{ route('outgoing.principal_review', $outgoing) }}">
                @csrf
                <div class="field">
                    <label for="modal-kepsek-note">Catatan persetujuan (wajib jika meminta revisi)</label>
                    <textarea class="textarea" id="modal-kepsek-note" name="kepsek_note" placeholder="Tulis catatan revisi bila diperlukan"></textarea>
                </div>
                <div class="form-actions">
                    <button class="button button-danger" name="decision" value="revisi" type="submit">Minta revisi</button>
                    <button class="button" name="decision" value="setuju" type="submit">Setujui dan siapkan cetak</button>
                </div>
            </form>
        @endif
    </section>
</div>
