@extends('layouts.app')
@section('title', 'Terbitkan sertifikat kegiatan')
@section('content')
<div class="page-heading"><div><span class="eyebrow blue">SERTIFIKAT KEGIATAN</span><h1>Terbitkan sertifikat</h1><p>Isi data untuk satu penerima. Setiap sertifikat mendapat nomor otomatis.</p></div></div>
<div class="event-certificate-form-layout">
<form class="form-card event-certificate-form" method="post" action="{{ route('event-certificates.store') }}">
    @csrf
    @php
        $selectedCertificateCategory = old('category', 'penghargaan');
        $defaultCertificateTitle = \App\Models\Certificate::DEFAULT_TITLES[$selectedCertificateCategory] ?? 'Sertifikat Kegiatan';
    @endphp
    <div class="form-grid">
        <div class="field"><label for="cert-category">Jenis sertifikat <span class="required">*</span></label><select class="select" id="cert-category" name="category" required>@foreach(\App\Models\Certificate::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected($selectedCertificateCategory === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="cert-title">Judul sertifikat <span class="required">*</span></label><input class="input" id="cert-title" name="title" maxlength="190" value="{{ old('title', $defaultCertificateTitle) }}" required><small class="field-hint">Jenis sertifikat juga tercetak pada desain.</small></div>
        <div class="field full"><label for="cert-recipient-name">Nama penerima <span class="required">*</span></label><input class="input" id="cert-recipient-name" name="recipient_name" maxlength="190" value="{{ old('recipient_name') }}" required placeholder="Nama lengkap penerima"></div>
        <div class="field full"><label for="cert-description">Uraian (template isi sertifikat) <span class="required">*</span></label><textarea class="textarea" id="cert-description" name="description" maxlength="1000" required placeholder="Mis. Sebagai Juara 1 Lomba Kompetensi Siswa Tingkat Kota Tanjungpinang">{{ old('description') }}</textarea></div>
        <div class="field"><label for="cert-date">Tanggal terbit <span class="required">*</span></label><input class="input" id="cert-date" type="date" name="issued_at" value="{{ old('issued_at', now()->format('Y-m-d')) }}" required></div>
        <div class="field"><label for="cert-signer-name">Nama penanda tangan pendamping <span class="required">*</span></label><input class="input" id="cert-signer-name" name="signer_name" maxlength="190" value="{{ old('signer_name') }}" required></div>
        <div class="field"><label for="cert-signer-title">Jabatan penanda tangan <span class="required">*</span></label><input class="input" id="cert-signer-title" name="signer_title" maxlength="190" value="{{ old('signer_title') }}" required></div>
        <div class="field"><label for="cert-signer-nip">NIP (opsional)</label><input class="input" id="cert-signer-nip" name="signer_nip" maxlength="50" value="{{ old('signer_nip') }}"></div>
    </div>
    <div class="form-actions"><a class="button button-light" href="{{ route('event-certificates.index') }}">Batal</a><button class="button">Terbitkan sertifikat</button></div>
</form>
<aside class="event-certificate-live-preview" aria-label="Pratinjau sertifikat">
    <div class="letter-live-preview-heading"><div><span class="eyebrow blue">PRATINJAU LANGSUNG</span><h2>Contoh sertifikat</h2><p>Pratinjau berubah mengikuti isian formulir.</p></div><span class="letter-preview-badge">DRAFT</span></div>
    <div class="certificate-preview">
        <article class="certificate certificate-category-{{ $selectedCertificateCategory }}">
            <div class="certificate-inner">
                <img class="certificate-watermark" src="{{ asset('images/tutwuri.png') }}" alt="" aria-hidden="true">
                <div class="certificate-logos"><img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau"><img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMKN 4 Tanjungpinang"></div>
                <h2 class="certificate-title event-certificate-title" data-certificate-preview="title">{{ $defaultCertificateTitle }}</h2>
                <p class="certificate-type-label" data-certificate-preview="category">{{ \App\Models\Certificate::CATEGORIES[$selectedCertificateCategory] ?? 'Lainnya' }}</p>
                <p class="cert-number" data-certificate-preview="number">Nomor: menunggu diterbitkan</p>
                <p class="cert-label">Diberikan Kepada</p>
                <p class="student-name event-certificate-name" data-certificate-preview="recipient">Nama penerima</p>
                <p class="cert-description event-certificate-description"><span data-certificate-preview="description">Uraian sertifikat akan tampil di sini</span><br><strong data-certificate-preview="date">{{ now()->locale('id')->translatedFormat('d F Y') }}</strong></p>
                <img class="certificate-slogan" src="{{ asset('images/slogan.png') }}" alt="SMK Bisa! SMK Hebat!">
                <div class="certificate-signatures">
                    <section class="certificate-signature"><p>KEPALA SEKOLAH</p><p>SMK NEGERI 4 TANJUNGPINANG</p><div class="signature-space" aria-hidden="true"></div><strong>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</strong><span>NIP 19770421 200502 2 011</span></section>
                    <section class="certificate-signature"><p data-certificate-preview="signer-title">JABATAN PENANDA TANGAN</p><p aria-hidden="true">&nbsp;</p><div class="signature-space" aria-hidden="true"></div><strong data-certificate-preview="signer-name">Nama penanda tangan</strong><span data-certificate-preview="signer-nip" hidden></span></section>
                </div>
            </div>
        </article>
    </div>
</aside>
</div>
@endsection
