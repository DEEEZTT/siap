@php
    $selectedRecipients = old('recipients', $disposition?->recipients ?? []);
    $selectedInstruction = old('instruction', $disposition?->other_instruction ? 'lainnya' : ($disposition?->instructions[0] ?? ''));
@endphp
<div class="field">
    <label>Diteruskan kepada Sdr.</label>
    <div class="check-grid checkbox-list">
        @foreach(['Waka Kurikulum', 'Waka Kesiswaan', 'Waka Sarpras', 'Waka Humas', 'Pembina OSIS', 'Koordinator TU', 'Bendahara Sekolah/BOS', 'Bendahara SPP'] as $recipient)
            <label class="check-item"><input type="checkbox" name="recipients[]" value="{{ $recipient }}" @checked(in_array($recipient, $selectedRecipients, true))> {{ $recipient }}</label>
        @endforeach
    </div>
    <input class="input" style="margin-top:8px" name="other_recipient" maxlength="190" value="{{ old('other_recipient', $disposition?->other_recipient) }}" placeholder="Sdr. lainnya (opsional)">
</div>
<div class="field" style="margin-top:14px">
    <label>Dengan hormat harap</label>
    <div class="disposition-radio-list">
        @foreach(['Tanggapan dan saran', 'Proses lebih lanjut', 'Koordinasi/konfirmasi'] as $instruction)
            <label class="disposition-radio-item"><input type="radio" name="instruction" value="{{ $instruction }}" @checked($selectedInstruction === $instruction)> {{ $instruction }}</label>
        @endforeach
        <div class="disposition-custom-instruction">
            <label class="disposition-radio-item"><input type="radio" name="instruction" value="lainnya" @checked($selectedInstruction === 'lainnya')> Lainnya</label>
            <input class="input" id="other-instruction" name="other_instruction" maxlength="190" value="{{ old('other_instruction', $disposition?->other_instruction) }}" placeholder="Isi arahan lainnya" aria-label="Arahan disposisi lainnya">
        </div>
    </div>
</div>
<div class="field" style="margin-top:14px">
    <label for="disposition-note">Catatan Kepala Sekolah</label>
    <textarea class="textarea" id="disposition-note" name="note" placeholder="Catatan atau arahan tindak lanjut">{{ old('note', $disposition?->note) }}</textarea>
</div>
<div class="field" style="margin-top:14px">
    <label for="disposition-attachment">Lampiran tambahan (opsional)</label>
    <input class="input" id="disposition-attachment" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png">
    @if($disposition?->attachment_path)
        <span class="field-hint">Lampiran saat ini: <a class="text-link" href="{{ route('documents.show', ['type' => 'disposition', 'id' => $disposition->id]) }}">Lihat lampiran</a>. Unggah file baru untuk menggantinya.</span>
    @endif
</div>
