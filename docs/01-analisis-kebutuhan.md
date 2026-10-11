# Analisis Kebutuhan — SIAP

## Pengguna
| Role | Tugas utama |
|---|---|
| Kepala TU (Admin) | Kelola akun, verifikasi surat keluar, lihat laporan & jejak audit, akses seluruh modul |
| Tata Usaha | Catat surat masuk, buat surat keluar, konfirmasi tindak lanjut disposisi, terbitkan sertifikat kegiatan |
| Kepala Sekolah | Disposisi surat masuk, setujui/revisi surat keluar, lihat laporan |
| Humas | Kelola mitra & MoU/PKS, siswa PKL, sertifikat PKL dan kegiatan, laporan kemitraan |

## Kebutuhan fungsional
- F1 Login, logout, pembagian hak akses per role, dashboard per role.
- F2 Data master pengguna (nama, username, email, role, jabatan, unit).
- F3 Surat masuk: pencatatan, nomor agenda otomatis, unggah arsip, pencarian/filter.
- F4 Disposisi: keputusan Kepsek, konfirmasi TU, riwayat, cetak.
- F5 Surat keluar: template, penomoran otomatis, alur verifikasi→persetujuan, cetak setelah disetujui.
- F6 Sertifikat: dari template + data penerima, penomoran, cetak/PDF (PKL dan kegiatan).
- F7 MoU/PKS: mitra, nomor dokumen, ruang lingkup, tanggal berlaku/berakhir, status, arsip berkas.
- F8 Pencarian/filter, riwayat/status dokumen, laporan, pengarsipan digital.

## Kebutuhan nonfungsional
- Responsif (komputer, tablet, ponsel).
- Validasi input sisi server; kata sandi minimal 8 karakter; throttle percobaan login.
- Berkas arsip disimpan privat (bukan folder publik) dan diakses lewat kontrol role.
- Surat berklasifikasi rahasia hanya terlihat oleh Kepala TU, Kepsek, dan TU pembuatnya.
- Jejak audit untuk perubahan akun, verifikasi/persetujuan surat, sertifikat, dan penghapusan.
- Penomoran tahan akses serentak (tabel urutan + kunci baris).
