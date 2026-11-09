<div class="modal-record-preview certificate-modal-preview">
    <div class="page-heading">
        <div>
            <span class="eyebrow blue">PRATINJAU SERTIFIKAT</span>
            <h1>{{ $certificate->recipient_name }}</h1>
            <p>{{ $certificate->certificate_number }} · {{ \App\Models\Certificate::CATEGORIES[$certificate->category] ?? $certificate->category }}</p>
        </div>
        <button class="button" type="button" onclick="window.print()">Cetak sertifikat / Simpan PDF</button>
    </div>
    @include('event-certificates.partials.certificate', ['certificate' => $certificate])
    @if(auth()->user()->role === 'kepala_tu')
        <div class="danger-zone hide-print">
            <p class="field-hint">Hapus sertifikat hanya jika penerbitannya keliru.</p>
            <form class="event-certificate-delete-form" method="post" action="{{ route('event-certificates.destroy', $certificate) }}">
                @csrf
                @method('delete')
                <button class="button button-danger" type="submit">Hapus sertifikat</button>
            </form>
        </div>
    @endif
</div>
