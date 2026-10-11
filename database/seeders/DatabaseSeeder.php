<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Buat akun awal. Aman dijalankan berulang: akun yang sudah ada
     * (berdasarkan username) tidak diubah dan passwordnya tidak di-reset.
     *
     * Password awal semua akun: siap12345 (segera ganti setelah login).
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Kepala TU', 'username' => 'kepala TU', 'email' => 'kepala_TU@gmail.com', 'role' => 'kepala_tu', 'position' => 'Kepala Tata Usaha', 'unit' => 'Tata Usaha'],
            ['name' => 'TU', 'username' => 'TU', 'email' => 'TU@gmail.com', 'role' => 'tu', 'position' => 'Staf Tata Usaha', 'unit' => 'Tata Usaha'],
            ['name' => 'HUMAS', 'username' => 'HUMAS', 'email' => 'HUMAS@gmail.com', 'role' => 'humas', 'position' => 'Staf Humas', 'unit' => 'Humas'],
            ['name' => 'kepsek', 'username' => 'kepsek', 'email' => 'kepsek@gmail.com', 'role' => 'kepsek', 'position' => 'Kepala Sekolah', 'unit' => 'Pimpinan'],
        ];

        foreach ($accounts as $account) {
            User::query()->firstOrCreate(
                ['username' => $account['username']],
                [...$account, 'password' => 'siap12345'],
            );
        }
    }
}
