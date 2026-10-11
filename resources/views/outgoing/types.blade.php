@extends('layouts.app')
@section('title', 'Pilih jenis surat keluar')
@section('content')
<div class="page-heading">
    <div>
        <span class="eyebrow blue">BUKU AGENDA SURAT KELUAR</span>
        <h1>Pilih jenis surat</h1>
        <p>Pilih format surat keluar yang ingin dibuat.</p>
    </div>
</div>
<div class="letter-type-grid">
    @foreach($types as $key => $letterType)
        <a class="letter-type-card" href="{{ route('outgoing.create.type', $key) }}">
            <span class="letter-type-icon" aria-hidden="true">{{ $key === 'undangan' ? '✉' : ($key === 'perintah_tugas' ? '▤' : '⇄') }}</span>
            <span class="letter-type-content">
                <strong>{{ $letterType['label'] }}</strong>
                <small>{{ $letterType['description'] }}</small>
            </span>
            <span class="letter-type-arrow" aria-hidden="true">→</span>
        </a>
    @endforeach
</div>
<div class="form-actions letter-type-actions">
    <a class="button button-light" href="{{ route('outgoing.index') }}">Kembali</a>
</div>
@endsection
