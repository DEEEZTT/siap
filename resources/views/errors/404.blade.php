@extends('layouts.app')
@section('title','Tidak ditemukan')
@section('content')<div class="error-page"><span class="eyebrow blue">404 · TIDAK DITEMUKAN</span><h1>Dokumen atau halaman tidak ditemukan.</h1><p>Periksa kembali tautan yang digunakan.</p><a class="button" href="{{ route('dashboard') }}">Kembali ke ringkasan</a></div>@endsection