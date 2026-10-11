@extends('layouts.app')
@section('title', isset($company) ? 'Ubah mitra' : 'Tambah mitra perusahaan')
@section('content')
<div class="page-heading"><div><span class="eyebrow blue">HUMAS · KERJA SAMA</span><h1>{{ isset($company) ? 'Ubah data mitra' : 'Tambah perusahaan mitra' }}</h1><p>Catat informasi kerja sama dan narahubung perusahaan.</p></div></div>
<form class="form-card" method="post" enctype="multipart/form-data" action="{{ isset($company) ? route('companies.update', $company) : route('companies.store') }}">
    @csrf
    @if(isset($company))@method('put')@endif
    <div class="form-grid">
        <div class="field"><label for="company-name">Nama perusahaan <span class="required">*</span></label><input class="input" id="company-name" name="name" value="{{ old('name', $company->name ?? '') }}" required></div>
        <div class="field"><label for="partnership-type">Jenis kemitraan <span class="required">*</span></label><select class="select" id="partnership-type" name="partnership_type" required><option value="">Pilih jenis mitra</option><option value="mou" @selected(old('partnership_type', $company->partnership_type ?? '') === 'mou')>Mitra resmi dengan MoU/PKS</option><option value="pkl_only" @selected(old('partnership_type', $company->partnership_type ?? '') === 'pkl_only')>Mitra PKL saja (tanpa MoU/PKS)</option></select><span class="field-hint">Data lama yang belum dipastikan akan tetap berstatus belum diklasifikasikan sampai diubah di sini.</span></div>
        <div class="field" data-agreement-field><label for="agreement-type">Jenis dokumen <span class="required">*</span></label><select class="select" id="agreement-type" name="agreement_type"><option value="">Pilih MoU atau PKS</option><option value="mou" @selected(old('agreement_type', $company->agreement_type ?? '') === 'mou')>MoU (Nota Kesepahaman)</option><option value="pks" @selected(old('agreement_type', $company->agreement_type ?? '') === 'pks')>PKS (Perjanjian Kerja Sama)</option></select></div>
        <div class="field" data-agreement-field><label for="document-number">Nomor dokumen <span class="required">*</span></label><input class="input" id="document-number" name="document_number" maxlength="120" value="{{ old('document_number', $company->document_number ?? '') }}"></div>
        <div class="field" data-agreement-field><label for="valid-from">Berlaku mulai <span class="required">*</span></label><input class="input" id="valid-from" type="date" name="valid_from" value="{{ old('valid_from', isset($company) ? $company->valid_from?->format('Y-m-d') : '') }}"></div>
        <div class="field" data-agreement-field><label for="valid-until">Berakhir pada <span class="required">*</span></label><input class="input" id="valid-until" type="date" name="valid_until" value="{{ old('valid_until', isset($company) ? $company->valid_until?->format('Y-m-d') : '') }}"></div>
        <div class="field full" data-agreement-field><label for="agreement-scope">Ruang lingkup kerja sama <span class="required">*</span></label><textarea class="textarea" id="agreement-scope" name="scope" maxlength="2000">{{ old('scope', $company->scope ?? '') }}</textarea></div>
        <div class="field full mou-upload-field" data-mou-upload @if(old('partnership_type', $company->partnership_type ?? '') !== 'mou') hidden @endif>
            <label for="mou-document">Arsip dokumen MoU/PKS (PDF, maksimal 10 MB)</label>
            <input class="input" id="mou-document" type="file" name="mou_document" accept=".pdf,application/pdf">
            <span class="field-hint">Berkas disimpan secara privat dan hanya dapat diunduh oleh pengguna Humas.</span>
        </div>
        @if(isset($company) && $company->mou_document_path)
            <div class="field full">
                <span class="field-hint">PDF MOU saat ini: <a class="text-link" href="{{ route('companies.mou.download', $company) }}">Unduh dokumen</a></span>
                <label class="mou-remove-option"><input type="checkbox" name="remove_mou_document" value="1" @checked(old('remove_mou_document'))> Hapus dokumen MOU saat menyimpan</label>
            </div>
        @endif
        <div class="field"><label for="company-status">Status kerja sama</label><select class="select" id="company-status" name="status"><option value="aktif" @selected(old('status', $company->status ?? 'aktif') === 'aktif')>Aktif</option><option value="tidak_aktif" @selected(old('status', $company->status ?? '') === 'tidak_aktif')>Tidak aktif</option></select></div>
        <div class="field"><label for="contact-person">Nama kontak perusahaan</label><input class="input" id="contact-person" name="contact_person" value="{{ old('contact_person', $company->contact_person ?? '') }}"></div>
        <div class="field"><label for="company-phone">Nomor telepon</label><input class="input" id="company-phone" name="phone" value="{{ old('phone', $company->phone ?? '') }}"></div>
        <div class="field"><label for="company-email">Email</label><input class="input" id="company-email" type="email" name="email" value="{{ old('email', $company->email ?? '') }}"></div>
        <div class="field"><label for="company-address">Alamat</label><textarea class="textarea" id="company-address" name="address">{{ old('address', $company->address ?? '') }}</textarea></div>
        <div class="field full"><label for="company-notes">Catatan kerja sama</label><textarea class="textarea" id="company-notes" name="notes">{{ old('notes', $company->notes ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="button button-light" href="{{ route('companies.index') }}">Batal</a><button class="button">Simpan perusahaan</button></div>
</form>
@if(isset($company) && !$company->students()->exists())
    <div class="danger-zone">
        <p class="field-hint">Hapus mitra hanya jika data ini memang tidak diperlukan.</p>
        <form method="post" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Hapus mitra {{ $company->name }}? Tindakan ini tidak bisa dibatalkan.')">
            @csrf
            @method('delete')
            <button class="button button-danger" type="submit">Hapus mitra</button>
        </form>
    </div>
@endif
@endsection
@pushOnce('scripts', 'company-mou-upload-toggle')
<script>
    const partnershipType = document.getElementById('partnership-type');
    const mouUploadField = document.querySelector('[data-mou-upload]');
    const mouDocumentInput = document.getElementById('mou-document');

    const agreementFields = document.querySelectorAll('[data-agreement-field]');

    const toggleMouUpload = () => {
        const isMou = partnershipType.value === 'mou';
        agreementFields.forEach((field) => {
            field.hidden = !isMou;
            field.querySelectorAll('input, select, textarea').forEach((input) => { input.disabled = !isMou; });
        });
        mouUploadField.hidden = !isMou;
        mouDocumentInput.disabled = !isMou;
        if (!isMou) {
            mouDocumentInput.value = '';
        }
    };

    partnershipType.addEventListener('change', toggleMouUpload);
    toggleMouUpload();
</script>
@endPushOnce
