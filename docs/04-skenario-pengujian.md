# Skenario Pengujian

Jalankan pengujian otomatis dari folder proyek:

```bash
php artisan test
```

Pengujian otomatis memakai database SQLite sementara. Kolom **Hasil UAT** hanya diisi setelah skenario dicoba oleh pengguna; jangan mengisi hasil UAT berdasarkan hasil tes otomatis.

## Cakupan dan hasil pemeriksaan

| No. | Skenario | Cakupan otomatis | Hasil UAT |
|---|---|---|---|
| 1 | Pembuatan akun Kepala TU pertama dan login | Pembuatan akun awal, username, sesi autentikasi, role Kepala TU, dan akses dashboard diuji; pembatasan percobaan login belum diuji otomatis. | |
| 2 | TU mencatat surat masuk dengan dan tanpa lampiran | Nomor agenda dan penyimpanan lampiran diuji. | |
| 3 | Kepala Sekolah mengisi disposisi, lalu TU mengonfirmasi tindak lanjut | Status menunggu konfirmasi, konfirmasi oleh TU, status selesai, dan identitas pencatat/konfirmasi diuji. | |
| 4 | Surat rahasia diakses TU lain | Akses daftar, detail, dan lampiran tanpa izin diuji. | |
| 5 | Surat keluar melalui verifikasi, persetujuan, dan cetak | Pemilihan jenis surat, pembuatan surat undangan, verifikasi Kepala TU, nomor agenda/surat, persetujuan Kepala Sekolah, dan cetak diuji. | |
| 6 | Penerbitan sertifikat PKL dan batas masa PKL | Penerbitan setelah PKL berakhir, nomor sertifikat, identitas penerima/mitra/predikat pada tampilan, dan penolakan sebelum PKL berakhir diuji. | |
| 7 | Penerbitan sertifikat kegiatan untuk beberapa penerima | Nomor berurutan, halaman detail, cetak semua, dan pembatasan role diuji. | |
| 8 | MoU/PKS tanpa nomor, ruang lingkup, atau tanggal | Validasi wajib diuji. | |
| 9 | Tanggal akhir MoU/PKS lebih awal dari tanggal mulai | Validasi tanggal diuji. | |
| 10 | MoU/PKS yang akan berakhir dalam 90 hari | Status, filter masa berlaku, dan peringatan dashboard diuji. | |
| 11 | Laporan per periode dan ekspor Excel | Role dan akses ekspor diuji; ketepatan rekap untuk kombinasi periode perlu diperiksa lebih lanjut. | |
| 12 | Perubahan akun dan akses jejak audit | Pencatatan perubahan akun dan pembatasan akses audit diuji. | |
| 13 | Tampilan komputer, tablet, dan ponsel | Pemeriksaan manual. Catat perangkat, ukuran layar, dan hasilnya di sini. | |
| 14 | Role membuka modul yang tidak berhak diakses | Sebagian pembatasan role diuji otomatis; lengkapi pemeriksaan untuk setiap role dan modul. | |

## Hasil eksekusi terakhir

Jalankan ulang `php artisan test` setelah perubahan aplikasi atau skenario tes. Catat jumlah tes lulus/gagal dan tindak lanjutnya di sini. Tes otomatis memeriksa perilaku kode; tes lulus tidak menggantikan UAT dan bukti kesesuaian dengan job sheet.

## Catatan UAT

Isi hasil UAT bersama pengguna sekolah setelah pengujian nyata. Catat minimal tanggal, nama/peran penguji, langkah yang dicoba, hasil aktual, bukti tangkapan layar bila perlu, dan tindak lanjut untuk setiap temuan.
