@extends('layouts.app')

@section('title', 'Ubah pengguna')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow blue">PENGATURAN AKUN</span>
            <h1>Ubah pengguna</h1>
            <p>Perbarui jabatan atau ganti kata sandi akun.</p>
        </div>
    </div>

    <form class="form-card" method="post" action="{{ route('users.update', $user) }}">
        @csrf
        @method('put')

        <div class="form-grid">
            <div class="field">
                <label>Nama lengkap *</label>
                <input class="input" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label>Username untuk login *</label>
                <input class="input" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" required>
            </div>
            <div class="field">
                <label>Email *</label>
                <input class="input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="field">
                <label>Jabatan *</label>
                <select class="select" name="role" required>
                    @foreach(['tu' => 'Tata Usaha', 'kepala_tu' => 'Kepala TU (Admin)', 'humas' => 'Humas', 'kepsek' => 'Kepala Sekolah'] as $role => $name)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Kata sandi baru <span class="field-hint">(kosongkan bila tetap)</span></label>
                <input class="input" type="password" name="password" minlength="8">
            </div>
            <div class="field">
                <label>Ulangi kata sandi baru</label>
                <input class="input" type="password" name="password_confirmation" minlength="8">
            </div>
        </div>

        <div class="form-actions">
            <a class="button button-light" href="{{ route('users.index') }}">Batal</a>
            <button class="button">Simpan perubahan</button>
        </div>
    </form>

    @if ($user->is(auth()->user()))
        <div class="user-delete-notice">
            <button class="button button-danger" type="button" disabled>Hapus akun</button>
            <span>Akun yang sedang digunakan tidak dapat dihapus.</span>
        </div>
    @else
        <form method="post" action="{{ route('users.destroy', $user) }}" data-confirm="Hapus akun pengguna ini?" class="user-delete-form">
            @csrf
            @method('delete')
            <button class="button button-danger">Hapus akun</button>
        </form>
    @endif
@endsection
