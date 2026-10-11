@extends('layouts.app')
@section('title','Akses ditolak')
@section('content')<div class="error-page"><span class="eyebrow blue">403 · AKSES TERBATAS</span><h1>Halaman ini tidak tersedia untuk jabatan Anda.</h1><p>Hubungi administrator jika Anda memerlukan akses ke modul ini.</p><a class="button" href="{{ route('dashboard') }}">Kembali ke ringkasan</a></div>@endsection