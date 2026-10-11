# Matriks Kesesuaian dengan Job Sheet

Matriks ini membandingkan spesifikasi produk di job sheet dengan bagian aplikasi yang tersedia. Keberadaan fitur di kode belum menggantikan bukti pengujian pengguna, deployment, dan kegiatan proyek.

| Spesifikasi | Implementasi aplikasi | Lokasi utama |
|---|---|---|
| Login, hak akses, dashboard, dan data master | Empat role, dashboard sesuai peran, serta pengelolaan akun, jabatan, dan unit | `AuthenticatedSessionController`, `EnsureRole`, `UserController`, `DashboardController` |
| Surat masuk, arsip, disposisi, dan tindak lanjut | Nomor agenda, unggah arsip privat, klasifikasi rahasia, disposisi Kepala Sekolah, konfirmasi TU, riwayat, dan cetak | `IncomingLetterController`, `DispositionController`, `DocumentController` |
| Surat keluar dengan nomor dan template | Tiga jenis surat, nomor otomatis, verifikasi Kepala TU, persetujuan/revisi Kepala Sekolah, dan cetak | `OutgoingLetterController`, `resources/views/outgoing/` |
| Sertifikat dari template dan daftar penerima | Sertifikat PKL dari data siswa; sertifikat kegiatan untuk beberapa penerima; nomor otomatis; cetak atau simpan PDF melalui browser | `CertificateController`, `EventCertificateController`, `resources/views/certificates/`, `resources/views/event-certificates/` |
| Data mitra dan MoU/PKS | Jenis kemitraan, nomor, ruang lingkup, tanggal berlaku, status otomatis, arsip PDF, filter masa berlaku, dan peringatan dashboard | `CompanyController`, `Company::agreementStatus()`, `DashboardController` |
| Pencarian/filter, status, laporan, dan arsip digital | Pencarian/filter pada daftar surat, mitra, siswa, sertifikat kegiatan, dan jejak audit; ringkasan laporan serta ekspor Excel | Controller modul terkait, `ReportController`, `AuditLogController` |
| Responsivitas, validasi, dan keamanan dasar | Media query, validasi sisi server, throttle login, berkas privat, dan pembatasan akses surat rahasia | `public/app.css`, controller terkait, `IncomingLetter::isVisibleTo()` |
| Dokumentasi analisis, desain, data, pengujian, dan panduan | Analisis kebutuhan, alur/navigasi, ERD, skenario uji, panduan pengguna, dan matriks ini | `docs/01` sampai `docs/06` |

## Bukti proyek yang perlu dilengkapi di luar kode

- Hasil UAT nyata bersama pengguna, termasuk temuan dan perbaikannya.
- Bukti pemeriksaan tampilan pada komputer, tablet, dan ponsel.
- Bukti deployment dan demo/presentasi produk.
- Laporan akhir, refleksi pembelajaran, pembagian tugas, dan validasi pihak terkait sesuai lembar job sheet.
- Tangkapan layar wireframe atau dokumentasi desain bila diminta guru pembimbing.

Lihat [skenario pengujian](04-skenario-pengujian.md) untuk hasil tes otomatis terakhir dan kolom pencatatan UAT.
