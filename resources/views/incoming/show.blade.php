@extends('layouts.app')
@section('title', 'Rincian surat masuk')
@section('content')
@php
    $canManageDisposition = in_array(auth()->user()->role, ['kepsek', 'kepala_tu'], true);
    $canConfirmFollowUp = in_array(auth()->user()->role, ['tu', 'kepala_tu'], true);
@endphp
<div class="page-heading">
    <div><span class="eyebrow blue">AGENDA {{ $incoming->agenda_number }}</span><h1>{{ $incoming->subject }}</h1><p>Surat dari {{ $incoming->sender }}</p></div>
    <a class="button button-light" href="{{ route('incoming.index') }}">← Kembali ke agenda</a>
</div>

<div class="detail-grid">
    <section class="detail-card">
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
            @if($incoming->file_path)
                <a class="button button-light" href="{{ route('documents.show', ['type' => 'incoming', 'id' => $incoming->id]) }}">Lihat berkas</a>
                <a class="button button-light" href="{{ route('documents.show', ['type' => 'incoming', 'id' => $incoming->id, 'download' => 1]) }}">Unduh</a>
            @else
                <span class="field-hint">Tidak ada berkas; surat dicatat manual.</span>
            @endif
            @if($incoming->disposition)
                <a class="button button-light" href="{{ route('incoming.disposition.print', $incoming) }}">Cetak lembar disposisi</a>
                @if($incoming->disposition->attachment_path)
                    <a class="button button-light" href="{{ route('documents.show', ['type' => 'disposition', 'id' => $incoming->disposition->id]) }}">Lihat lampiran disposisi</a>
                @endif
            @endif
            @if(in_array(auth()->user()->role, ['tu', 'kepala_tu'], true) && $incoming->status === 'menunggu_disposisi')
                <a class="button button-light" href="{{ route('incoming.edit', $incoming) }}">Ubah data</a>
            @endif
        </div>
        @if(auth()->user()->role === 'kepala_tu')
            <div class="danger-zone">
                <p class="field-hint">Penghapusan juga menghapus disposisi dan lampirannya, termasuk untuk surat yang sudah selesai.</p>
                <form method="post" action="{{ route('incoming.destroy', $incoming) }}" onsubmit="return confirm('Hapus surat masuk {{ $incoming->agenda_number }} beserta disposisi dan lampirannya? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('delete')
                    <button class="button button-danger" type="submit">Hapus surat masuk</button>
                </form>
            </div>
        @endif
    </section>

    <section class="detail-card">
        <h2>Lembar disposisi</h2>
        @if($incoming->disposition)
            <dl class="detail-list">
                <dt>Diteruskan kepada</dt><dd>{{ implode(', ', $incoming->disposition->recipients ?? []) }}{{ $incoming->disposition->other_recipient ? ', '.$incoming->disposition->other_recipient : '' }}</dd>
                <dt>Instruksi</dt><dd>{{ implode(', ', $incoming->disposition->instructions ?? []) }} {{ $incoming->disposition->other_instruction }}</dd>
                <dt>Catatan</dt><dd>{{ $incoming->disposition->note ?: '—' }}</dd>
                <dt>Terakhir diubah oleh</dt><dd>{{ $incoming->disposition->completer?->name }} · {{ $incoming->disposition->completed_at?->format('d/m/Y H:i') }}</dd>
                <dt>Konfirmasi TU</dt><dd>{{ $incoming->disposition->confirmer?->name ? $incoming->disposition->confirmer->name.' · '.$incoming->disposition->confirmed_at?->format('d/m/Y H:i') : 'Menunggu konfirmasi tindak lanjut' }}</dd>
                @if($incoming->disposition->confirmation_note)
                    <dt>Catatan konfirmasi</dt><dd>{{ $incoming->disposition->confirmation_note }}</dd>
                @endif
            </dl>

            @if($canManageDisposition)
                <div class="document-actions"><a class="button button-light" href="{{ route('incoming.disposition.edit', $incoming) }}">Ubah disposisi</a></div>
            @endif

            @if($canConfirmFollowUp && $incoming->status === 'menunggu_konfirmasi_tu')
                <form class="disposition-form follow-up-confirmation" method="post" action="{{ route('incoming.follow_up.confirm', $incoming) }}">
                    @csrf
                    <div class="field">
                        <label for="confirmation-note">Catatan tindak lanjut (opsional)</label>
                        <textarea class="textarea" id="confirmation-note" name="confirmation_note" placeholder="Contoh: disposisi sudah diteruskan kepada Waka Humas"></textarea>
                    </div>
                    <button class="button" type="submit">Konfirmasi tindak lanjut</button>
                </form>
            @endif

            @if(count($incoming->disposition->revision_history ?? []) > 0)
                <details class="disposition-history">
                    <summary>Riwayat perubahan ({{ count($incoming->disposition->revision_history) }})</summary>
                    @foreach(array_reverse($incoming->disposition->revision_history) as $entry)
                        <div class="history-entry">
                            <b>{{ ucwords(str_replace('_', ' ', $entry['action'] ?? 'perubahan')) }}</b>
                            <small>{{ $entry['user_name'] ?? 'Pengguna dihapus' }}{{ !empty($entry['user_role']) ? ' ('.strtoupper(str_replace('_', ' ', $entry['user_role'])).')' : '' }} · {{ $entry['at'] ?? '—' }}</small>
                            @if(!empty($entry['after']))
                                <ul>
                                    @foreach($entry['after'] as $field => $value)
                                        @if(($entry['before'][$field] ?? null) !== $value)
                                            <li>{{ ucfirst(str_replace('_', ' ', $field)) }}: {{ is_array($entry['before'][$field] ?? null) ? implode(', ', $entry['before'][$field]) : ($entry['before'][$field] ?? '—') }} → {{ is_array($value) ? implode(', ', $value) : ($value ?: '—') }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </details>
            @endif
        @elseif($canManageDisposition && $incoming->status === 'menunggu_disposisi')
            <p class="muted">Isi arahan yang akan diteruskan. Tata Usaha mengonfirmasi tindak lanjut setelah disposisi disimpan.</p>
            <form method="post" enctype="multipart/form-data" action="{{ route('incoming.disposition', $incoming) }}" class="disposition-form">
                @csrf
                @include('components.disposition-form', ['disposition' => null])
                <button class="button" style="margin-top:15px">Simpan disposisi</button>
            </form>
        @elseif($incoming->status === 'menunggu_konfirmasi_tu')
            <div class="empty-state compact">Menunggu Tata Usaha mengonfirmasi tindak lanjut disposisi.</div>
        @else
            <div class="empty-state compact">Belum ada disposisi untuk surat ini.</div>
        @endif
    </section>
</div>
@endsection
