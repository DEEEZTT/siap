# Use Case, Alur Proses, dan Navigasi

```mermaid
flowchart LR
  TU([Tata Usaha]) --> A[Catat surat masuk]
  TU --> B[Buat draft surat keluar]
  TU --> C[Konfirmasi tindak lanjut]
  TU --> S[Terbitkan sertifikat kegiatan]
  KS([Kepala Sekolah]) --> D[Disposisi surat masuk]
  KS --> E[Setujui / revisi surat keluar]
  KT([Kepala TU / Admin]) --> F[Verifikasi surat keluar]
  KT --> G[Kelola akun pengguna]
  KT --> H[Lihat jejak audit]
  HM([Humas]) --> I[Kelola mitra & MoU/PKS]
  HM --> J[Data siswa PKL & sertifikat PKL]
  KT & KS & TU & HM --> L[Laporan]
```

## Alur surat masuk & disposisi
Catat (TU) → menunggu disposisi → Kepsek mengisi disposisi → menunggu konfirmasi TU → TU konfirmasi → selesai.

## Alur surat keluar
Draft (TU) → menunggu verifikasi → Kepala TU: setuju (nomor dibuat) atau revisi → menunggu persetujuan Kepsek → setuju (siap dicetak) atau revisi.

## Status masa berlaku MoU/PKS (otomatis)
Data belum lengkap · Belum berlaku · Berlaku · Segera berakhir (≤ 90 hari) · Kedaluwarsa.

## Struktur navigasi
Ringkasan · Surat masuk · Surat keluar · Laporan · Sertifikat kegiatan · (Humas) Data siswa PKL, Mitra & MoU/PKS · (Admin) Pengguna, Jejak audit.
