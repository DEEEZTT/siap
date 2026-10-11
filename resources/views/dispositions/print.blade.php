@extends('layouts.app')
@section('title', 'Lembar disposisi')
@section('content')
<div class="print-toolbar hide-print">
    <a class="button button-light" href="{{ $letterUrl }}">← Kembali</a>
    <button class="button" type="button" onclick="window.print()">Cetak lembar disposisi / Simpan PDF</button>
</div>

<main class="disposition-print-page">
    <article class="disposition-copy">
            @php
                $selectedInstruction = $disposition->instructions[0] ?? null;
                $customInstructionSelected = ! $selectedInstruction && filled($disposition->other_instruction);
            @endphp
            <header class="disposition-header">
                <img src="{{ asset('images/kepri.png') }}" alt="Lambang Provinsi Kepulauan Riau">
                <div>
                    <strong>PEMERINTAH PROVINSI KEPULAUAN RIAU</strong>
                    <b>DINAS PENDIDIKAN</b>
                    <b>SMK NEGERI 4 TANJUNGPINANG</b>
                    <small>Jalan Nusantara Km. 14, Batu IX, Tanjungpinang, Kepulauan Riau 29125</small>
                    <small>Telepon (0771) 4414084 · NPSN 11002495 · NSS 4013161010</small>
                    <small>Laman smkn4-tanjungpinang.sch.id · Pos-el smkn4tpi@gmail.com</small>
                </div>
                <img src="{{ asset('images/smkn4.png') }}" alt="Lambang SMKN 4 Tanjungpinang">
            </header>
            <h1 class="disposition-heading">LEMBAR DISPOSISI</h1>

            <table class="disposition-info">
                <tbody>
                    <tr><th>Surat dari</th><td>{{ $sender }}</td><th>Diterima Tgl.</th><td>{{ $received }}</td></tr>
                    <tr><th>No. Surat</th><td>{{ $number }}</td><th>No. Agenda</th><td>{{ $agenda }}</td></tr>
                    <tr>
                        <th>Tgl. Surat</th><td>{{ $date }}</td>
                        <th class="classification-label">Sifat</th>
                        <td class="classification-cell">
                            <div class="classification-options">
                                @foreach(['sangat_segera' => 'Sangat segera', 'segera' => 'Segera', 'rahasia' => 'Rahasia', 'biasa' => 'Biasa'] as $value => $label)
                                    <span><i>{{ $classification === $value ? '✓' : '' }}</i>{{ $label }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    <tr><th>Hal</th><td colspan="3">{{ $subject }}</td></tr>
                </tbody>
            </table>

            <div class="disposition-routing">
                <section>
                    <b>Diteruskan kepada Sdr.</b>
                    @foreach(['Waka Kurikulum', 'Waka Kesiswaan', 'Waka Sarpras', 'Waka Humas', 'Pembina OSIS', 'Koordinator TU', 'Bendahara Sekolah/BOS', 'Bendahara SPP'] as $recipient)
                        <span class="disposition-choice"><i>{{ in_array($recipient, $disposition->recipients ?? [], true) ? '✓' : '' }}</i>{{ $recipient }}</span>
                    @endforeach
                    <span class="disposition-custom"><i>{{ $disposition->other_recipient ? '✓' : '' }}</i>Sdr. {{ $disposition->other_recipient ?: '........................................................................' }}</span>
                </section>
                <section>
                    <b>Dengan hormat harap:</b>
                    @foreach(['Tanggapan dan saran', 'Proses lebih lanjut', 'Koordinasi/konfirmasi'] as $instruction)
                        <span class="disposition-choice"><i>{{ $selectedInstruction === $instruction ? '✓' : '' }}</i>{{ $instruction }}</span>
                    @endforeach
                    <span class="disposition-custom"><i>{{ $customInstructionSelected ? '✓' : '' }}</i>{{ $disposition->other_instruction ?: '........................................................................' }}</span>
                    <span class="disposition-dotted-line">............................................................................</span>
                </section>
            </div>

            <footer class="disposition-footer">
                <section><b>Catatan:</b><p>{{ $disposition->note }}</p></section>
                <section class="disposition-signature">
                    <span>Kepala Sekolah,</span>
                    <strong>Yayuk Sri Mulyani Rahayu, S.Pd., M.M.</strong>
                    <small>Pembina Tk. I</small>
                    <small>NIP 19770421 200502 2 011</small>
                </section>
            </footer>
    </article>
</main>
@endsection
