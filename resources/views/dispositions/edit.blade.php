@extends('layouts.app')
@section('title', 'Ubah disposisi')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow blue">AGENDA {{ $incoming->agenda_number }}</span><h1>Ubah disposisi</h1><p>{{ $incoming->subject }} · {{ $incoming->sender }}</p></div>
    <a class="button button-light" href="{{ route('incoming.show', $incoming) }}">Kembali ke surat</a>
</div>
<form class="form-card disposition-edit-card" method="post" enctype="multipart/form-data" action="{{ route('incoming.disposition.update', $incoming) }}">
    @csrf
    @method('put')
    @include('components.disposition-form', ['disposition' => $incoming->disposition])
    <div class="form-actions">
        <a class="button button-light" href="{{ route('incoming.show', $incoming) }}">Batal</a>
        <button class="button" type="submit">Simpan perubahan dan minta konfirmasi TU</button>
    </div>
</form>
@endsection
