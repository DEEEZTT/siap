# Panduan Instalasi SIAP

Panduan ini untuk menjalankan SIAP di komputer lokal dengan PHP, Composer, dan MySQL/MariaDB. Pilih **satu** cara menyiapkan database pada langkah 3.

## 1. Persiapan

Pastikan sudah tersedia:

- PHP 8.3 atau lebih baru, dengan ekstensi `mbstring`, `pdo_mysql`, `openssl`, `fileinfo`, dan `zip`.
- Composer.
- MySQL atau MariaDB yang sedang berjalan.

Buka terminal di folder proyek `siap`, lalu pasang dependensi:

```bash
composer install
```

Jika diminta php 8.4 jalankan:

```bash
composer update
```

## 2. Atur koneksi aplikasi

Salin `.env.example` menjadi `.env`.

```bash
cp .env.example .env
```

Isi pengaturan database di `.env` sesuai MySQL lokal Anda:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siap
DB_USERNAME=root
DB_PASSWORD=
```

Ganti `DB_USERNAME` dan `DB_PASSWORD` bila akun MySQL Anda berbeda. Buat database kosong bernama `siap` melalui phpMyAdmin atau MySQL. Setelah itu buat kunci aplikasi:

```bash
php artisan key:generate
```
buat data base bernama `siap`

## 3. Pilih cara menyiapkan data

### A. Instalasi baru dengan data demo terbaru — disarankan untuk mencoba aplikasi

Gunakan database `siap` yang masih kosong. Jalankan:

```bash
php artisan migrate --seed
php artisan db:seed --class=DemoDataSeeder
```

Perintah pertama membuat tabel dan empat akun awal. Perintah kedua mengisi data fiktif untuk mitra, siswa, surat, disposisi, dan sertifikat agar fitur pencarian serta filter bisa dicoba. Data akun yang sudah ada tidak diubah oleh seeder demo.

Akun awal dari seeder:

| Peran | Username | Kata sandi awal |
|---|---|---|
| Kepala TU | `kepala TU` | `siap12345` |
| Tata Usaha | `TU` | `siap12345` |
| Humas | `HUMAS` | `siap12345` |
| Kepala Sekolah | `kepsek` | `siap12345` |

Segera ganti kata sandi setelah masuk. Seeder demo hanya dapat dijalankan untuk MySQL lokal dengan database `siap` pada host `127.0.0.1`.

### B. Instalasi baru dengan akun saja

Gunakan database `siap` yang masih kosong. Jalankan:

```bash
php artisan migrate --seed
```

Perintah ini membuat tabel dan empat akun awal seperti pada pilihan A, tanpa mengisi data demo.

### C. Instalasi kosong dan buat akun pengelola sendiri

Gunakan database `siap` yang masih kosong. Jalankan:

```bash
php artisan migrate
```

Buka aplikasi. Karena belum ada akun, halaman awal akan mengarahkan Anda untuk membuat akun Kepala TU pertama.

## 4. Jalankan aplikasi

```bash
php artisan serve
```

Buka <http://127.0.0.1:8000> di browser. Login menggunakan **username**, bukan alamat email.

## Catatan penting

- Periksa kembali `DB_DATABASE`, `DB_HOST`, dan akun MySQL sebelum menjalankan perintah database.
- Jangan jalankan `php artisan migrate:fresh` pada database berisi data. Perintah itu menghapus tabel beserta isinya.
- `DemoDataSeeder` membuat data fiktif untuk pengujian lokal. Jangan gunakan data demo sebagai data operasional sekolah.
- Untuk melihat status migrasi, jalankan `php artisan migrate:status`.

## Pemecahan masalah

- **Koneksi database gagal:** pastikan MySQL aktif, database `siap` sudah dibuat, dan nilai `DB_*` pada `.env` benar.
- **Perubahan `.env` tidak terbaca:** jalankan `php artisan config:clear`, lalu coba lagi.
- **Perintah PHP tidak dikenali:** pastikan PHP sudah terpasang dan masuk ke `PATH`, lalu buka terminal baru.
