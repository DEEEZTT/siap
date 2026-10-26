# SIAP — Sistem Manajemen Sekolah

Aplikasi web untuk membantu administrasi SMK Negeri 4 Tanjungpinang. Proyek ini dibuat oleh XII RPL 2 dalam pembelajaran Teaching Factory.

## Fitur

| Modul | Kemampuan utama |
|---|---|
| Akun dan hak akses | Login, dashboard per peran, serta pengelolaan akun, jabatan, dan unit kerja |
| Surat masuk dan disposisi | Nomor agenda, arsip privat, klasifikasi surat, disposisi, konfirmasi tindak lanjut, riwayat, dan cetak |
| Surat keluar | Template undangan, perintah tugas, dan rekomendasi mutasi; verifikasi, persetujuan, penomoran, dan cetak |
| Data mitra dan PKL | Mitra MoU/PKS dan mitra PKL, masa berlaku dokumen, data siswa, impor/ekspor Excel, dan sertifikat PKL |
| Sertifikat kegiatan | Penerbitan untuk banyak penerima, nomor otomatis, pencarian, cetak, dan simpan PDF |
| Laporan | Rekap surat dan sertifikat menurut periode, status MoU/PKS terkini, cetak, dan ekspor Excel |
| Keamanan dasar | Validasi sisi server, pembatasan percobaan login, arsip privat, aturan akses surat rahasia, dan jejak audit |

## Menjalankan aplikasi

Ikuti [Panduan instalasi](SETUP.md). Pilihan data demo terbaru memakai `DemoDataSeeder`; data yang dibuat bersifat fiktif dan ditujukan untuk mencoba aplikasi lokal.

## Dokumentasi proyek

- [Analisis kebutuhan](docs/01-analisis-kebutuhan.md)
- [Use case, alur proses, dan navigasi](docs/02-use-case-dan-navigasi.md)
- [Rancangan basis data](docs/03-basis-data.md)
- [Skenario pengujian](docs/04-skenario-pengujian.md)
- [Panduan pengguna](docs/05-panduan-pengguna.md)
- [Matriks kesesuaian job sheet](docs/06-matriks-kesesuaian-jobsheet.md)

## Pengujian

Jalankan pengujian otomatis dengan:

```bash
php artisan test
```

Lihat [skenario pengujian](docs/04-skenario-pengujian.md) untuk cakupan otomatis dan pemeriksaan manual. Hasil UAT perlu diisi berdasarkan uji coba pengguna yang benar-benar dilakukan.

## Anggota kelompok

|nama|
|---|
|Ibrahim Mufid|
|Afdallil Zhikri|
|Frendika Dwiyuda Prastya|
|Said Kevin Valentsyah|
|Muhammad Haikal|
|Fadhil Rahman Falah|
|Eris Saputra|