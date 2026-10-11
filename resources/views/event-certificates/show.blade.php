@extends('layouts.app')
@section('title', 'Sertifikat kegiatan')
@section('content')
<div class="page-heading hide-print">
    <div><span class="eyebrow blue">SERTIFIKAT KEGIATAN</span><h1>{{ $certificate->recipient_name }}</h1><p>{{ $certificate->certificate_number }} · {{ \App\Models\Certificate::CATEGORIES[$certificate->category] ?? $certificate->category }}</p></div>
    <div class="row-actions"><a class="button button-light" href="{{ route('event-certificates.index') }}">← Daftar sertifikat</a><button class="button" onclick="window.print()">Cetak sertifikat / Simpan PDF</button></div>
</div>
@include('event-certificates.partials.certificate', ['certificate' => $certificate])
@if(auth()->user()->role === 'kepala_tu')
    <div class="danger-zone hide-print">
        <p class="field-hint">Hapus sertifikat hanya jika penerbitannya keliru.</p>
        <form method="post" action="{{ route('event-certificates.destroy', $certificate) }}" data-confirm="Hapus sertifikat ini? Tindakan ini tidak bisa dibatalkan.">
            @csrf
            @method('delete')
            <button class="button button-danger" type="submit">Hapus sertifikat</button>
        </form>
    </div>
@endif
@endsection
