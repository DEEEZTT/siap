<div class="certificate-preview">
    <article class="certificate">
        <div class="certificate-inner">
            <img class="certificate-watermark" src="{{ asset('images/tutwuri.png') }}" alt="" aria-hidden="true">
            <div class="certificate-logos">
                <img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
                <img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMKN 4 Tanjungpinang">
            </div>

            <h2 class="certificate-title event-certificate-title">{{ $certificate->title }}</h2>
            <p class="cert-number">Nomor: {{ $certificate->certificate_number }}</p>
            <p class="cert-label">Diberikan Kepada</p>
            <p class="student-name event-certificate-name">{{ strtoupper($certificate->recipient_name) }}</p>
            <p class="cert-description event-certificate-description">
                {{ $certificate->description }}<br>
                <strong>{{ $certificate->issued_at->locale('id')->translatedFormat('d F Y') }}</strong>
            </p>

            <img class="certificate-slogan" src="{{ asset('images/slogan.png') }}" alt="SMK Bisa! SMK Hebat!">

            <div class="certificate-signatures">
                <section class="certificate-signature">
                    <p>KEPALA SEKOLAH</p>
                    <p>SMK NEGERI 4 TANJUNGPINANG</p>
                    <div class="signature-space" aria-hidden="true"></div>
                    <strong>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</strong>
                    <span>NIP 19770421 200502 2 011</span>
                </section>
                <section class="certificate-signature">
                    <p>{{ strtoupper($certificate->signer_title) }}</p>
                    <p aria-hidden="true">&nbsp;</p>
                    <div class="signature-space" aria-hidden="true"></div>
                    <strong>{{ $certificate->signer_name }}</strong>
                    @if($certificate->signer_nip)<span>NIP {{ $certificate->signer_nip }}</span>@endif
                </section>
            </div>
        </div>
    </article>
</div>
