@extends('layouts.app')
@section('title', 'Cetak semua sertifikat')
@section('content')
<div class="print-toolbar hide-print">
    <button class="button" type="button" onclick="window.print()">Cetak / Simpan semua sebagai PDF</button>
    <span>{{ $certificates->count() }} sertifikat. Pada dialog cetak, pilih “Simpan sebagai PDF”.</span>
    <a class="button button-light" href="{{ route('event-certificates.index') }}">← Kembali</a>
</div>
@foreach($certificates as $certificate)
    <div class="no-break">@include('event-certificates.partials.certificate', ['certificate' => $certificate])</div>
@endforeach
@endsection
