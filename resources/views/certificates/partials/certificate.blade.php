<div class="certificate-preview">
        <article class="certificate">
            <div class="certificate-inner">
                <img class="certificate-watermark" src="{{ asset('images/tutwuri.png') }}" alt="" aria-hidden="true">
                <div class="certificate-logos">
                    <img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
                    <img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMKN 4 Tanjungpinang">
                </div>

                <h2 class="certificate-title">Sertifikat</h2>
                <p class="cert-number">Nomor: {{ $student->certificate_number }}</p>
                <p class="cert-label">Diberikan Kepada</p>
                <p class="student-name">{{ strtoupper($student->name) }}</p>

                <dl class="student-facts">
                    <dt>Nomor Induk Siswa</dt>
                    <dd>{{ $student->nis }}</dd>
                    <dt>Tempat / Tgl. Lahir</dt>
                    <dd>{{ $student->birth_place ?: '—' }} / {{ $student->birth_date?->locale('id')->translatedFormat('d F Y') ?? '—' }}</dd>
                    <dt>Konsentrasi Keahlian</dt>
                    <dd>{{ $student->major }}</dd>
                </dl>

                <p class="cert-description">
                    Telah selesai mengikuti Praktik Kerja Lapangan (PKL) pada<br>
                    <strong>{{ $student->company?->name ?? 'Mitra Dunia Kerja' }}</strong> dengan Predikat <strong>{{ strtoupper($student->predicate) }}</strong>
                </p>

                <img class="certificate-slogan" src="{{ asset('images/slogan.png') }}" alt="SMK Bisa! SMK Hebat!">

                <div class="certificate-signatures" aria-label="Kolom tanda tangan dibiarkan kosong">
                    <section class="certificate-signature">
                        <p>KEPALA SEKOLAH</p>
                        <p>SMK NEGERI 4 TANJUNGPINANG</p>
                        <div class="signature-space" aria-hidden="true"></div>
                        <strong>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</strong>
                        <span>NIP 19770421 200502 2 011</span>
                    </section>

                    <section class="certificate-signature">
                        <p>{{ strtoupper($student->certificate_signer_title ?? 'JABATAN MITRA PKL') }}</p>
                        <p>{{ strtoupper($student->company?->name ?? 'TEMPAT PKL') }}</p>
                        <div class="signature-space" aria-hidden="true"></div>
                        <strong>{{ $student->certificate_signer_name }}</strong>
                        @if($student->certificate_signer_nip)
                            <span>NIP {{ $student->certificate_signer_nip }}</span>
                        @endif
                    </section>
                </div>
            </div>
        </article>
    </div>
