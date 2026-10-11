@extends('layouts.app')
@section('title', 'Terbitkan sertifikat kegiatan')
@section('content')
<div class="page-heading"><div><span class="eyebrow blue">SERTIFIKAT KEGIATAN</span><h1>Terbitkan sertifikat</h1><p>Isi data kegiatan sekali, lalu masukkan daftar penerima. Nomor sertifikat dibuat otomatis berurutan.</p></div></div>
<form class="form-card" method="post" action="{{ route('event-certificates.store') }}">
    @csrf
    <div class="form-grid">
        <div class="field"><label for="cert-category">Jenis sertifikat <span class="required">*</span></label><select class="select" id="cert-category" name="category" required>@foreach(\App\Models\Certificate::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label for="cert-title">Judul sertifikat <span class="required">*</span></label><input class="input" id="cert-title" name="title" maxlength="190" value="{{ old('title', 'Sertifikat Penghargaan') }}" required></div>
        <div class="field full"><label for="cert-description">Uraian (template isi sertifikat) <span class="required">*</span></label><textarea class="textarea" id="cert-description" name="description" maxlength="1000" required placeholder="Mis. Sebagai Juara 1 Lomba Kompetensi Siswa Tingkat Kota Tanjungpinang">{{ old('description') }}</textarea></div>
        <div class="field"><label for="cert-date">Tanggal terbit <span class="required">*</span></label><input class="input" id="cert-date" type="date" name="issued_at" value="{{ old('issued_at', now()->format('Y-m-d')) }}" required></div>
        <div class="field"><label for="cert-signer-name">Nama penanda tangan pendamping <span class="required">*</span></label><input class="input" id="cert-signer-name" name="signer_name" maxlength="190" value="{{ old('signer_name') }}" required></div>
        <div class="field"><label for="cert-signer-title">Jabatan penanda tangan <span class="required">*</span></label><input class="input" id="cert-signer-title" name="signer_title" maxlength="190" value="{{ old('signer_title') }}" required></div>
        <div class="field"><label for="cert-signer-nip">NIP (opsional)</label><input class="input" id="cert-signer-nip" name="signer_nip" maxlength="50" value="{{ old('signer_nip') }}"></div>
        <div class="field full"><label for="cert-recipients">Daftar penerima <span class="required">*</span></label><textarea class="textarea" id="cert-recipients" name="recipients" rows="8" required placeholder="Satu penerima per baris. Keterangan tambahan opsional setelah tanda |&#10;Ahmad Fauzi | XII RPL 3&#10;Siti Aisyah | XII TKJ 1">{{ old('recipients') }}</textarea><span class="field-hint">Maksimal 200 penerima. Format: Nama | keterangan (kelas/instansi).</span></div>
    </div>
    <div class="form-actions"><a class="button button-light" href="{{ route('event-certificates.index') }}">Batal</a><button class="button">Terbitkan sertifikat</button></div>
</form>
@endsection
