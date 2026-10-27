<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Fills the local SIAP database with clearly fictional, searchable demo records.
 * Safe to rerun: records use stable unique keys, and existing user accounts are never changed.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        app()->bind(\League\MimeTypeDetection\MimeTypeDetector::class, function () {
            return new \League\MimeTypeDetection\ExtensionMimeTypeDetector();
        });

        $connection = config('database.connections.mysql');
        // if (($connection['database'] ?? null) !== 'siap' || ($connection['host'] ?? null) !== '127.0.0.1') {
        //     throw new RuntimeException('DemoDataSeeder hanya boleh dijalankan pada database lokal siap.');
        // }

        $users = [
            'tu' => User::query()->where('role', 'tu')->firstOrFail(),
            'kepala_tu' => User::query()->where('role', 'kepala_tu')->firstOrFail(),
            'humas' => User::query()->where('role', 'humas')->firstOrFail(),
            'kepsek' => User::query()->where('role', 'kepsek')->firstOrFail(),
        ];

        DB::transaction(function () use ($users): void {
            $companies = $this->seedCompanies();
            $students = $this->seedStudents($companies);
            $incoming = $this->seedIncoming($users);
            $outgoing = $this->seedOutgoing($users, $students);
            $this->seedDispositions($incoming, $outgoing, $users);
            $this->seedCertificates($users);
            $this->seedActivityLogs($users);
        });
    }

    /** @return list<Company> */
    private function seedCompanies(): array
    {
        $today = today();
        $specs = [
            ['mou', 'mou', 'aktif', 'valid'],
            ['mou', 'pks', 'aktif', 'soon'],
            ['mou', 'mou', 'tidak_aktif', 'expired'],
            ['mou', 'pks', 'aktif', 'incomplete'],
            ['mou', 'mou', 'aktif', 'future'],
            ['mou', 'pks', 'aktif', 'valid'],
            ['mou', 'mou', 'tidak_aktif', 'expired'],
            ['mou', 'pks', 'aktif', 'soon'],
            ['pkl_only', null, 'aktif', 'none'],
            ['pkl_only', null, 'tidak_aktif', 'none'],
            ['pkl_only', null, 'aktif', 'none'],
            ['pkl_only', null, 'aktif', 'none'],
            [null, null, 'aktif', 'none'],
            [null, null, 'tidak_aktif', 'none'],
        ];
        $places = ['Batu 9', 'Tanjungpinang Kota', 'Bintan Timur', 'Senggarang', 'Dompak', 'Tanjung Uban', 'Kijang', 'Toapaya', 'Penyengat', 'Gunung Kijang', 'Teluk Sebong', 'Lagoi', 'Kampung Bugis', 'Air Raja'];
        $companies = [];

        foreach ($specs as $index => [$partnership, $agreement, $status, $validity]) {
            $number = $index + 1;
            [$validFrom, $validUntil] = match ($validity) {
                'valid' => [$today->copy()->subDays(180), $today->copy()->addDays(240 + $number)],
                'soon' => [$today->copy()->subDays(240), $today->copy()->addDays(20 + $number * 4)],
                'expired' => [$today->copy()->subYear(), $today->copy()->subDays(10 + $number)],
                'future' => [$today->copy()->addDays(20), $today->copy()->addDays(220)],
                'incomplete' => [null, null],
                default => [null, null],
            };
            $name = sprintf('Mitra Demo %02d %s', $number, ['Cakrawala', 'Samudra', 'Nusantara', 'Teknologi', 'Bahari', 'Kreasi', 'Sejahtera'][$index % 7]);
            $documentPath = $partnership === 'mou' ? sprintf('demo-data/mitra-%02d.pdf', $number) : null;

            if ($documentPath) {
                $this->putDemoPdf($documentPath, 'ARSIP MITRA DEMO '.$number, 'Dokumen contoh. Bukan dokumen kerja sama resmi.');
            }

            $companies[] = Company::query()->firstOrCreate(['name' => $name], [
                'partnership_type' => $partnership,
                'agreement_type' => $agreement,
                'document_number' => $partnership === 'mou' ? sprintf('DEMO-KS-%04d/%s', $number, now()->year) : null,
                'scope' => $partnership === 'mou' ? sprintf('Ruang lingkup demo %02d: praktik industri dan dukungan pembelajaran bidang %s.', $number, ['perangkat lunak', 'multimedia', 'jaringan', 'siaran', 'desain'][$index % 5]) : null,
                'valid_from' => $validFrom?->toDateString(),
                'valid_until' => $validUntil?->toDateString(),
                'address' => sprintf('Jl. Contoh %d, Kelurahan %s, Kota Tanjungpinang', 10 + $number, $places[$index]),
                'contact_person' => sprintf('Kontak Demo %02d', $number),
                'phone' => sprintf('08120000%04d', $number),
                'email' => sprintf('mitra%02d@example.test', $number),
                'status' => $status,
                'notes' => sprintf('DATA DEMO %02d — profil rekaan untuk mencoba pencarian nama, alamat, kontak, email, dan nomor dokumen.', $number),
                'mou_document_path' => $documentPath,
            ]);
        }

        return $companies;
    }

    /** @param list<Company> $companies
     *  @return list<Student>
     */
    private function seedStudents(array $companies): array
    {
        $firstNames = ['Alya', 'Rafi', 'Nadia', 'Fikri', 'Citra', 'Dimas', 'Intan', 'Rizky', 'Nabila', 'Farhan', 'Putri', 'Ardi', 'Salsa', 'Ilham', 'Maya', 'Rendra', 'Tiara', 'Bagas', 'Zahra', 'Rama', 'Dewi', 'Akbar', 'Laras', 'Yusuf'];
        $lastNames = ['Pratama', 'Permata', 'Saputra', 'Kusuma', 'Ramadhan', 'Pertiwi', 'Maulana', 'Lestari', 'Wijaya', 'Anindya', 'Firmansyah', 'Utami'];
        $places = ['Tanjungpinang', 'Bintan', 'Dompak', 'Kijang', 'Senggarang', 'Toapaya'];
        $years = ['2026/2027', '2025/2026', '2024/2025'];
        $students = [];
        $today = today();

        for ($index = 1; $index <= 24; $index++) {
            $completed = $index % 5 === 0;
            $name = $firstNames[$index - 1].' '.$lastNames[($index - 1) % count($lastNames)].sprintf(' Demo %02d', $index);
            $start = $today->copy()->subDays(150 + $index * 3);
            $end = $completed ? $today->copy()->subDays(5 + $index) : $today->copy()->addDays(12 + $index * 3);
            $company = $index % 7 === 0 ? null : $companies[($index * 3) % count($companies)];
            $nis = sprintf('260%04d', $index);
            $major = Student::MAJORS[($index - 1) % count(Student::MAJORS)];
            $certificateNumber = $completed ? sprintf('PKL-DEMO-%s-%03d', now()->year, $index) : null;
            $predicate = $completed ? ['Cukup', 'Baik', 'Sangat Baik'][($index - 1) % 3] : ($index % 3 === 0 ? ['Cukup', 'Baik', 'Sangat Baik'][($index - 1) % 3] : null);

            $students[] = Student::query()->firstOrCreate(
                ['nis' => $nis, 'academic_year' => $years[($index - 1) % count($years)]],
                [
                    'name' => $name,
                    'birth_place' => $places[($index - 1) % count($places)].sprintf(' Area %02d', $index),
                    'birth_date' => $today->copy()->subYears(16 + ($index % 3))->subDays($index * 13)->toDateString(),
                    'major' => $major,
                    'company_id' => $company?->id,
                    'internship_start' => $start->toDateString(),
                    'internship_end' => $end->toDateString(),
                    'predicate' => $predicate,
                    'certificate_number' => $certificateNumber,
                    'completed_at' => $completed ? $today->copy()->subDays($index)->setTime(10 + ($index % 7), 15)->toDateTimeString() : null,
                    'certificate_signer_name' => $completed ? sprintf('Penandatangan PKL Demo %02d', $index) : null,
                    'certificate_signer_title' => $completed ? 'Kepala Sekolah — contoh' : null,
                    'certificate_signer_nip' => $completed ? sprintf('1980%02d%02d 200501 1 %03d', ($index % 12) + 1, ($index % 27) + 1, $index) : null,
                ],
            );
        }

        return $students;
    }

    /** @param array<string, User> $users
     *  @return list<IncomingLetter>
     */
    private function seedIncoming(array $users): array
    {
        $statuses = [
            'menunggu_disposisi', 'menunggu_disposisi', 'menunggu_disposisi', 'menunggu_disposisi',
            'menunggu_konfirmasi_tu', 'menunggu_konfirmasi_tu', 'menunggu_konfirmasi_tu', 'menunggu_konfirmasi_tu',
            'selesai', 'selesai', 'selesai', 'selesai',
        ];
        $classifications = ['biasa', 'segera', 'sangat_segera', 'rahasia'];
        $senders = ['Dinas Pendidikan', 'Cabang Dinas Pendidikan', 'Puskesmas Dompak', 'Komite Sekolah', 'Dinas Perpustakaan', 'Politeknik Kepulauan Riau', 'Kelurahan Batu IX', 'SMK Bahari', 'Badan Bahasa', 'Dinas Lingkungan Hidup', 'Kecamatan Bukit Bestari', 'Balai Latihan Kerja'];
        $letters = [];
        $today = today();

        foreach ($statuses as $offset => $status) {
            $number = $offset + 1;
            $received = $today->copy()->subMonths($offset % 10)->subDays(2 + $offset * 2);
            $agenda = sprintf('IM/%s/%04d', $today->format('Y'), 900 + $number);
            $path = sprintf('demo-data/surat-masuk-%02d.pdf', $number);
            $this->putDemoPdf($path, 'SURAT MASUK DEMO '.$number, 'Lampiran contoh untuk pengujian SIAP. Bukan surat resmi.');

            $letters[] = IncomingLetter::query()->firstOrCreate(['agenda_number' => $agenda], [
                'letter_number' => sprintf('%03d/DEMO/%s/%04d', $number, strtoupper($received->format('m')), $received->year),
                'letter_date' => $received->copy()->subDays(4 + ($number % 3))->toDateString(),
                'received_at' => $received->toDateString(),
                'sender' => $senders[$offset].sprintf(' — Unit Demo %02d', $number),
                'subject' => sprintf('Perihal Demo %02d: %s', $number, ['permohonan data pendidikan', 'undangan koordinasi sekolah', 'pemberitahuan layanan masyarakat', 'permintaan tindak lanjut kegiatan'][$offset % 4]),
                'classification' => $classifications[$offset % count($classifications)],
                'file_path' => $path,
                'notes' => sprintf('CATATAN DEMO %02d — gunakan pencarian nomor agenda, nomor surat, pengirim, atau perihal.', $number),
                'status' => $status,
                'created_by' => $users['tu']->id,
            ]);
            $letters[array_key_last($letters)]->created_by = $users['tu']->id;
            $letters[array_key_last($letters)]->save();
        }

        $this->advanceAgendaSequence('incoming', (int) $today->format('Y'), 900 + count($statuses));

        return $letters;
    }

    /** @param list<IncomingLetter> $letters
     *  @param array<string, User> $users
     */
    private function seedDispositions(array $letters, array $outgoingLetters, array $users): void
    {
        $recipients = ['Waka Kurikulum', 'Waka Kesiswaan', 'Waka Sarpras', 'Waka Humas', 'Pembina OSIS', 'Koordinator TU', 'Bendahara Sekolah/BOS', 'Bendahara SPP'];
        $instructions = ['Tanggapan dan saran', 'Proses lebih lanjut', 'Koordinasi/konfirmasi'];
        $today = now();

        foreach (array_slice($letters, 4) as $offset => $letter) {
            $number = $offset + 1;
            $confirmed = $letter->status === 'selesai';
            $completedAt = $today->copy()->subDays(10 + $number)->setTime(9 + ($number % 7), 20);
            $attachmentPath = $number % 2 === 0 ? sprintf('demo-data/disposisi-%02d.pdf', $number) : null;
            if ($attachmentPath) {
                $this->putDemoPdf($attachmentPath, 'LEMBAR DISPOSISI DEMO '.$number, 'Lampiran contoh; tidak memiliki kekuatan administratif.');
            }

            Disposition::query()->firstOrCreate(['incoming_letter_id' => $letter->id], [
                'outgoing_letter_id' => null,
                'recipients' => [$recipients[$offset % count($recipients)], $recipients[($offset + 2) % count($recipients)]],
                'other_recipient' => $number % 3 === 0 ? sprintf('Koordinator Program Demo %02d', $number) : null,
                'instructions' => $number % 4 === 0 ? [] : [$instructions[$offset % count($instructions)]],
                'other_instruction' => $number % 4 === 0 ? sprintf('Instruksi contoh khusus %02d', $number) : null,
                'note' => sprintf('Disposisi demo %02d untuk mencoba catatan tindak lanjut dan riwayat.', $number),
                'attachment_path' => $attachmentPath,
                'completed_by' => $users['kepsek']->id,
                'completed_at' => $completedAt,
                'confirmed_by' => $confirmed ? $users['tu']->id : null,
                'confirmed_at' => $confirmed ? $completedAt->copy()->addDays(1 + $number) : null,
                'confirmation_note' => $confirmed ? sprintf('Tindak lanjut contoh %02d telah diteruskan ke unit terkait.', $number) : null,
                'revision_history' => [[
                    'action' => 'disposisi_dibuat',
                    'user_id' => $users['kepsek']->id,
                    'user_name' => $users['kepsek']->name,
                    'user_role' => 'kepsek',
                    'at' => $completedAt->toIso8601String(),
                    'before' => null,
                    'after' => ['recipients' => [$recipients[$offset % count($recipients)]], 'has_attachment' => (bool) $attachmentPath],
                ]],
            ]);
        }

        foreach ([2, 7, 11] as $offset) {
            $letter = $outgoingLetters[$offset];
            $number = $offset + 1;
            $attachmentPath = sprintf('demo-data/disposisi-surat-keluar-%02d.pdf', $number);
            $this->putDemoPdf($attachmentPath, 'DISPOSISI SURAT KELUAR DEMO '.$number, 'Lampiran contoh untuk uji cetak disposisi lama.');

            Disposition::query()->firstOrCreate(['outgoing_letter_id' => $letter->id], [
                'incoming_letter_id' => null,
                'recipients' => ['Waka Kurikulum', 'Koordinator TU'],
                'other_recipient' => sprintf('Unit tujuan demo %02d', $number),
                'instructions' => ['Koordinasi/konfirmasi'],
                'other_instruction' => null,
                'note' => sprintf('Disposisi surat keluar demo %02d.', $number),
                'attachment_path' => $attachmentPath,
                'completed_by' => $users['kepsek']->id,
                'completed_at' => now()->subDays(15 + $number),
                'confirmed_by' => $users['tu']->id,
                'confirmed_at' => now()->subDays(10 + $number),
                'confirmation_note' => sprintf('Konfirmasi demo surat keluar %02d.', $number),
                'revision_history' => [[
                    'action' => 'disposisi_dibuat',
                    'user_id' => $users['kepsek']->id,
                    'user_name' => $users['kepsek']->name,
                    'user_role' => 'kepsek',
                    'at' => now()->subDays(15 + $number)->toIso8601String(),
                    'before' => null,
                    'after' => ['recipients' => ['Waka Kurikulum', 'Koordinator TU'], 'has_attachment' => true],
                ]],
            ]);
        }
    }

    /** @param array<string, User> $users
     *  @param list<Student> $students
     *  @return list<OutgoingLetter>
     */
    private function seedOutgoing(array $users, array $students): array
    {
        $statuses = ['draft', 'menunggu_verifikasi', 'perlu_revisi', 'menunggu_persetujuan_kepsek', 'perlu_revisi_kepsek', 'siap_dicetak', 'selesai', 'menunggu_verifikasi', 'draft', 'menunggu_persetujuan_kepsek', 'perlu_revisi', 'siap_dicetak', 'selesai', 'perlu_revisi_kepsek'];
        $types = ['undangan', 'perintah_tugas', 'rekomendasi_mutasi'];
        $today = today();
        $highestNumber = 0;
        $processedMonth = 0;

        $letters = [];
        foreach ($statuses as $offset => $status) {
            $number = $offset + 1;
            $type = $types[$offset % count($types)];
            $processed = ! in_array($status, ['draft', 'menunggu_verifikasi', 'perlu_revisi'], true);
            $verified = ! in_array($status, ['draft', 'menunggu_verifikasi'], true);
            $reviewed = in_array($status, ['perlu_revisi_kepsek', 'siap_dicetak', 'selesai'], true);
            $agendaNumber = $processed ? sprintf('SK/%s/%04d', $today->format('Y'), 1200 + $number) : null;
            $highestNumber = $processed ? max($highestNumber, 1200 + $number) : $highestNumber;
            if ($processed) {
                $processedMonth++;
            }
            $letterDate = $processed ? $today->copy()->setDate($today->year, min($processedMonth, $today->month), 10)->toDateString() : null;
            $verifiedAt = $verified && $letterDate ? Carbon::parse($letterDate)->subDay()->setTime(13, 30) : null;
            $reviewedAt = $reviewed && $letterDate ? Carbon::parse($letterDate)->addDay()->setTime(14, 0) : null;
            $details = match ($type) {
                'undangan' => ['invitation_context' => sprintf('Kegiatan demo koordinasi pendidikan nomor %02d.', $number), 'grade_class' => sprintf('%s-%d DEMO', Student::MAJORS[($offset) % count(Student::MAJORS)], 10 + ($offset % 3))],
                'perintah_tugas' => ['basis' => sprintf('Surat tugas demo nomor %02d.', $number), 'rank' => sprintf('Penata Demo %02d', $number), 'employee_number' => sprintf('NIP-DEMO-%04d', $number), 'position' => sprintf('Koordinator Kegiatan Demo %02d', $number), 'assignment' => sprintf('Melaksanakan tugas pencatatan dan pelaporan kegiatan contoh nomor %02d.', $number)],
                default => $this->transferDetails($students[$offset % count($students)], $number),
            };
            $recipient = $type === 'undangan' ? 'Orang tua/Wali Murid kelas '.$details['grade_class'] : sprintf('Penerima Demo %02d', $number);
            $subject = sprintf('%s Demo %02d — %s', ['Undangan', 'Perintah Tugas', 'Rekomendasi Mutasi'][$offset % 3], $number, ['kegiatan literasi', 'koordinasi program', 'layanan pendidikan'][$offset % 3]);
            $path = in_array($status, ['siap_dicetak', 'selesai'], true) ? sprintf('demo-data/surat-keluar-%02d.pdf', $number) : null;
            if ($path) {
                $this->putDemoPdf($path, 'SURAT KELUAR DEMO '.$number, 'Pratinjau contoh untuk pengujian. Bukan surat resmi.');
            }

            $letter = OutgoingLetter::query()->firstOrCreate(['agenda_number' => $agendaNumber, 'subject' => $subject], [
                'letter_number' => $processed ? sprintf('400.3/DEMO/%04d/SMKN4/%s', $number, $today->format('Y')) : null,
                'type' => $type,
                'letter_date' => $letterDate,
                'recipient_name' => $recipient,
                'recipient_address' => sprintf('Alamat tujuan demo %02d, Kota Tanjungpinang', $number),
                'subject' => $subject,
                'event_date' => $type === 'undangan' ? $today->copy()->addDays(15 + $number)->toDateString() : null,
                'event_time' => $type === 'undangan' ? sprintf('%02d.%02d WIB', 8 + ($number % 8), ($number * 5) % 60) : null,
                'event_place' => $type === 'undangan' ? sprintf('Aula Demo Gedung %02d', $number) : null,
                'body' => sprintf('Isi surat demo %02d. Seluruh nama, penerima, kegiatan, dan keterangan pada record ini adalah data rekaan untuk pengujian SIAP.', $number),
                'details' => $details,
                'notes' => sprintf('Catatan surat keluar demo %02d dengan data unik untuk pencarian.', $number),
                'file_path' => $path,
                'status' => $status,
                'verification_note' => $status === 'perlu_revisi' ? sprintf('Mohon lengkapi rincian contoh %02d sebelum diajukan kembali.', $number) : ($verified ? sprintf('Verifikasi demo %02d selesai.', $number) : null),
                'created_by' => $users['tu']->id,
                'verified_by' => $verified ? $users['kepala_tu']->id : null,
                'verified_at' => $verifiedAt,
                'kepsek_note' => $status === 'perlu_revisi_kepsek' ? sprintf('Mohon revisi isi contoh %02d.', $number) : ($status === 'siap_dicetak' || $status === 'selesai' ? sprintf('Disetujui sebagai contoh %02d.', $number) : null),
                'kepsek_reviewed_by' => $reviewed ? $users['kepsek']->id : null,
                'kepsek_reviewed_at' => $reviewedAt,
            ]);
            $letter->created_by = $users['tu']->id;
            $letter->letter_date = $letterDate;
            $letter->verified_at = $verifiedAt;
            $letter->kepsek_reviewed_at = $reviewedAt;
            $letter->save();
            $letters[] = $letter;
        }

        if ($highestNumber > 0) {
            $this->advanceAgendaSequence('outgoing', (int) $today->format('Y'), $highestNumber);
        }

        return $letters;
    }

    /** @return array<string, string> */
    private function transferDetails(Student $student, int $number): array
    {
        return [
            'nisn' => sprintf('00%08d', 300000 + $number),
            'origin_school' => sprintf('SMK Contoh Asal %02d', $number),
            'destination_school' => sprintf('SMK Contoh Tujuan %02d', $number),
            'class_info' => sprintf('XI %s Demo', $student->major),
            'origin_school_address' => sprintf('Jl. Sekolah Asal Demo %02d, Tanjungpinang', $number),
            'birth_place' => $student->birth_place,
            'birth_date' => $student->birth_date->toDateString(),
            'father_name' => sprintf('Wali Ayah Demo %02d', $number),
            'father_occupation' => sprintf('Pekerjaan Ayah Demo %02d', $number),
            'mother_name' => sprintf('Wali Ibu Demo %02d', $number),
            'mother_occupation' => sprintf('Pekerjaan Ibu Demo %02d', $number),
            'student_profile' => sprintf('Siswa demo %s, konsentrasi %s.', $student->name, $student->major),
            'reason' => sprintf('Alasan perpindahan pendidikan demo nomor %02d.', $number),
            'attachments' => sprintf('Lampiran data siswa demo %02d.', $number),
        ];
    }

    /** @param array<string, User> $users */
    private function seedCertificates(array $users): void
    {
        $categories = array_keys(Certificate::CATEGORIES);
        $today = today();
        $highestNumbers = [];

        for ($index = 1; $index <= 12; $index++) {
            $year = $today->year - (($index - 1) % 3);
            $number = sprintf('SRT/%s/%04d', $year, 700 + $index);
            $category = $categories[($index - 1) % count($categories)];

            Certificate::query()->firstOrCreate(['certificate_number' => $number], [
                'category' => $category,
                'recipient_name' => sprintf('Penerima Sertifikat Demo %02d', $index),
                'recipient_detail' => sprintf('%s — peserta rekaan kegiatan sekolah nomor %02d', ['Siswa kelas X', 'Siswa kelas XI', 'Guru pendamping', 'Narasumber tamu'][$index % 4], $index),
                'title' => sprintf('Sertifikat Demo %02d: %s', $index, ['Pekan Literasi', 'Pameran Karya', 'Pelatihan Digital', 'Lomba Inovasi'][$index % 4]),
                'description' => sprintf('Sertifikat contoh nomor %02d untuk menguji kategori, judul, keterangan, dan pencarian penerima.', $index),
                'issued_at' => $today->copy()->setYear($year)->subDays($index * 9)->toDateString(),
                'signer_name' => sprintf('Penandatangan Demo %02d', $index),
                'signer_title' => sprintf('Kepala Kegiatan Demo %02d', $index),
                'signer_nip' => sprintf('1979%02d%02d 200101 1 %03d', ($index % 12) + 1, ($index % 27) + 1, $index),
                'created_by' => $index % 2 === 0 ? $users['tu']->id : $users['humas']->id,
            ]);
            $highestNumbers[$year] = max($highestNumbers[$year] ?? 0, 700 + $index);
        }

        foreach ($highestNumbers as $year => $number) {
            $this->advanceAgendaSequence('certificate', (int) $year, $number);
        }
    }

    /** @param array<string, User> $users */
    private function seedActivityLogs(array $users): void
    {
        foreach (['mitra_demo_dibuat', 'siswa_demo_dibuat', 'surat_masuk_demo_dicatat', 'surat_keluar_demo_dibuat', 'sertifikat_demo_diterbitkan'] as $index => $action) {
            $subjectIds = [
                Company::query()->where('name', 'like', 'Mitra Demo%')->value('id'),
                Student::query()->where('nis', 'like', '260%')->value('id'),
                IncomingLetter::query()->where('agenda_number', 'like', 'IM/%')->value('id'),
                OutgoingLetter::query()->whereNotNull('agenda_number')->value('id'),
                Certificate::query()->where('certificate_number', 'like', 'SRT/%')->value('id'),
            ];
            $log = ActivityLog::query()->firstOrCreate(
                ['action' => $action, 'description' => sprintf('Log demo %02d: aktivitas rekaan untuk mencoba pencarian jejak audit.', $index + 1)],
                [
                    'user_id' => array_values($users)[$index % count($users)]->id,
                    'subject_type' => ['Company', 'Student', 'IncomingLetter', 'OutgoingLetter', 'Certificate'][$index],
                    'subject_id' => $subjectIds[$index],
                    'ip_address' => sprintf('192.0.2.%d', 10 + $index),
                    'created_at' => now()->subHours($index * 7),
                ],
            );
            $log->subject_type = ['Company', 'Student', 'IncomingLetter', 'OutgoingLetter', 'Certificate'][$index];
            $log->subject_id = $subjectIds[$index];
            $log->ip_address = sprintf('192.0.2.%d', 10 + $index);
            $log->created_at = now()->subHours($index * 7);
            $log->save();
        }
    }

    private function advanceAgendaSequence(string $type, int $year, int $number): void
    {
        $query = DB::table('agenda_sequences')->where('sequence_type', $type)->where('sequence_year', $year);
        $sequence = $query->first();
        if ($sequence) {
            $query->update(['last_number' => max((int) $sequence->last_number, $number), 'updated_at' => now()]);

            return;
        }

        DB::table('agenda_sequences')->insert([
            'sequence_type' => $type,
            'sequence_year' => $year,
            'last_number' => $number,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function putDemoPdf(string $path, string $heading, string $body): void
    {
        $fullPath = storage_path('app/' . $path);
        
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, $this->pdf($heading, $body));
    }

    private function pdf(string $heading, string $body): string
    {
        $toPdfText = static function (string $text): string {
            $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: 'DEMO';

            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        };
        $content = 'BT /F1 18 Tf 48 750 Td ('.$toPdfText($heading).') Tj /F1 11 Tf 0 -30 Td ('.$toPdfText($body).') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF\n";
    }
}
