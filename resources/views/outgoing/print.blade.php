@extends('layouts.app')
@section('title', 'Pratinjau '.$outgoing->subject)
@section('content')
<div class="print-toolbar hide-print">
    <a class="button button-light" href="{{ route('outgoing.show', $outgoing) }}">← Kembali</a>
    <button class="button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
@include('outgoing.document')
@endsection
