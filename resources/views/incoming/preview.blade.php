<div class="modal-record-preview">
    @php
        $canManageDisposition = in_array(auth()->user()->role, ['kepsek', 'kepala_tu'], true);
        $canConfirmFollowUp = in_array(auth()->user()->role, ['tu', 'kepala_tu'], true);
    @endphp
    <div class="page-heading">
        <div>
            <span class="eyebrow blue">AGENDA {{ $incoming->agenda_number }}</span>
            <h1>{{ $incoming->subject }}</h1>
            <p>Surat dari {{ $incoming->sender }}</p>
        </div>
    </div>
    <section class="detail-card record-detail-card">
        <span class="detail-card-kicker">RINGKASAN DOKUMEN</span>
        <h2>Informasi surat</h2>
        <dl class="detail-list">
            <dt>Nomor agenda</dt><dd>{{ $incoming->agenda_number }}</dd>
            <dt>Nomor dan tanggal surat</dt><dd>{{ $incoming->letter_number }} · {{ $incoming->letter_date->format('d/m/Y') }}</dd>
            <dt>Tanggal diterima</dt><dd>{{ $incoming->received_at->format('d/m/Y') }}</dd>
            <dt>Alamat asal</dt><dd>{{ $incoming->sender }}</dd>
            <dt>Perihal</dt><dd>{{ $incoming->subject }}</dd>
            <dt>Sifat</dt><dd>{{ ucfirst(str_replace('_', ' ', $incoming->classification)) }}</dd>
            <dt>Keterangan</dt><dd>{{ $incoming->notes ?: '—' }}</dd>
            <dt>Status</dt><dd><span class="status status-{{ $incoming->status }}">{{ str_replace('_', ' ', $incoming->status) }}</span></dd>
        </dl>
        <div class="document-actions">
            @if(in_array(auth()->user()->role, ['tu', 'kepala_tu'], true) && $incoming->status === 'menunggu_disposisi')
                <a class="button button-light" href="{{ route('incoming.edit', $incoming) }}" data-incoming-edit-modal>Ubah data surat</a>
            @endif
            @if($incoming->file_path)
                <a class="button button-light" href="{{ route('documents.show', ['type' => 'incoming', 'id' => $incoming->id]) }}" target="_blank" rel="noopener">Lihat berkas</a>
                <a class="button button-light" href="{{ route('documents.show', ['type' => 'incoming', 'id' => $incoming->id, 'download' => 1]) }}" target="_blank" rel="noopener">Unduh</a>
            @else
                <span class="field-hint">Tidak ada berkas; surat dicatat manual.</span>
            @endif
        </div>
    </section>
    @if($incoming->disposition)
        <section class="detail-card record-detail-card modal-disposition-card">
            <span class="detail-card-kicker">TINDAK LANJUT</span>
            <h2>Lembar disposisi</h2>
            <dl class="detail-list">
                <dt>Diteruskan kepada</dt><dd>{{ implode(', ', $incoming->disposition->recipients ?? []) }}{{ $incoming->disposition->other_recipient ? ', '.$incoming->disposition->other_recipient : '' }}</dd>
                <dt>Instruksi</dt><dd>{{ implode(', ', $incoming->disposition->instructions ?? []) }} {{ $incoming->disposition->other_instruction }}</dd>
                <dt>Catatan</dt><dd>{{ $incoming->disposition->note ?: '—' }}</dd>
                <dt>Terakhir diubah oleh</dt><dd>{{ $incoming->disposition->completer?->name }} · {{ $incoming->disposition->completed_at?->format('d/m/Y H:i') }}</dd>
                <dt>Konfirmasi TU</dt><dd>{{ $incoming->disposition->confirmer?->name ? $incoming->disposition->confirmer->name.' · '.$incoming->disposition->confirmed_at?->format('d/m/Y H:i') : 'Menunggu konfirmasi tindak lanjut' }}</dd>
                @if($incoming->disposition->confirmation_note)<dt>Catatan konfirmasi</dt><dd>{{ $incoming->disposition->confirmation_note }}</dd>@endif
            </dl>
            <div class="document-actions">
                <a class="button button-light js-print-disposition" href="{{ route('incoming.disposition.print', $incoming) }}">Cetak lembar disposisi</a>
                @if($incoming->disposition->attachment_path)
                    <a class="button button-light" href="{{ route('documents.show', ['type' => 'disposition', 'id' => $incoming->disposition->id]) }}" target="_blank" rel="noopener">Lihat lampiran disposisi</a>
                @endif
            </div>
            @if($canConfirmFollowUp && $incoming->status === 'menunggu_konfirmasi_tu')
                <form class="disposition-form follow-up-confirmation" method="post" action="{{ route('incoming.follow_up.confirm', $incoming) }}">
                    @csrf
                    <button class="button" type="submit">Disposisi selesai</button>
                </form>
            @endif
        </section>
    @elseif($canManageDisposition && $incoming->status === 'menunggu_disposisi')
        <section class="detail-card record-detail-card modal-disposition-card">
            <span class="detail-card-kicker">TINDAKAN BERIKUTNYA</span>
            <h2>Buat disposisi</h2>
            <form method="post" enctype="multipart/form-data" action="{{ route('incoming.disposition', $incoming) }}" data-disposition-required class="disposition-form modal-disposition-form">
                @csrf
                @include('components.disposition-form', ['disposition' => null])
                <button class="button" type="submit">Simpan disposisi</button>
            </form>
        </section>
    @endif
</div>
