# Skenario Pengujian

Jalankan pengujian otomatis dari folder proyek:

```bash
php artisan test
```

Pengujian otomatis memakai database SQLite sementara. Kolom **Hasil UAT** hanya diisi setelah skenario dicoba oleh pengguna; jangan mengisi hasil UAT berdasarkan hasil tes otomatis.

## Cakupan dan hasil pemeriksaan

| No. | Skenario | Cakupan otomatis | Hasil UAT |
|---|---|---|---|
| 1 | Pembuatan akun Kepala TU pertama dan login | Sebagian. Tes setup saat ini gagal karena tidak mengirim `username`; pembatasan percobaan login belum diuji otomatis. | |
| 2 | TU mencatat surat masuk dengan dan tanpa lampiran | Nomor agenda dan penyimpanan lampiran diuji. | |
| 3 | Kepala Sekolah mengisi disposisi, lalu TU mengonfirmasi tindak lanjut | Tes yang ada mengharapkan status langsung selesai setelah disposisi; alur aplikasi sekarang masih menunggu konfirmasi TU. Tes perlu diselaraskan. | |
| 4 | Surat rahasia diakses TU lain | Akses daftar, detail, dan lampiran tanpa izin diuji. | |
| 5 | Surat keluar melalui verifikasi, persetujuan, dan cetak | Ada tes alur, tetapi saat ini gagal pada pemeriksaan teks pilihan jenis surat sehingga langkah berikutnya belum tervalidasi oleh tes tersebut. | |
| 6 | Sertifikat PKL sebelum PKL berakhir | Penolakan penerbitan sebelum waktunya diuji. Tes tampilan sertifikat juga perlu menyamakan huruf besar/kecil pada predikat. | |
| 7 | Penerbitan sertifikat kegiatan untuk beberapa penerima | Nomor berurutan, halaman detail, cetak semua, dan pembatasan role diuji. | |
| 8 | MoU/PKS tanpa nomor, ruang lingkup, atau tanggal | Validasi wajib diuji. | |
| 9 | Tanggal akhir MoU/PKS lebih awal dari tanggal mulai | Validasi tanggal diuji. | |
| 10 | MoU/PKS yang akan berakhir dalam 90 hari | Status, filter masa berlaku, dan peringatan dashboard diuji. | |
| 11 | Laporan per periode dan ekspor Excel | Role dan akses ekspor diuji; ketepatan rekap untuk kombinasi periode perlu diperiksa lebih lanjut. | |
| 12 | Perubahan akun dan akses jejak audit | Pencatatan perubahan akun dan pembatasan akses audit diuji. | |
| 13 | Tampilan komputer, tablet, dan ponsel | Pemeriksaan manual. Catat perangkat, ukuran layar, dan hasilnya di sini. | |
| 14 | Role membuka modul yang tidak berhak diakses | Sebagian pembatasan role diuji otomatis; lengkapi pemeriksaan untuk setiap role dan modul. | |

## Hasil eksekusi terakhir

Pada pemeriksaan terakhir, `php artisan test` menjalankan **27 tes: 23 lulus dan 4 gagal**. Kegagalan yang tercatat berkaitan dengan input `username` pada tes setup, ekspektasi status disposisi, teks pilihan surat keluar, dan huruf besar/kecil predikat sertifikat PKL. Tinjau atau perbarui tes tersebut, lalu jalankan ulang sebelum menyatakan seluruh tes lulus.

## Catatan UAT

Isi hasil UAT bersama pengguna sekolah setelah pengujian nyata. Catat minimal tanggal, nama/peran penguji, langkah yang dicoba, hasil aktual, bukti tangkapan layar bila perlu, dan tindak lanjut untuk setiap temuan.
