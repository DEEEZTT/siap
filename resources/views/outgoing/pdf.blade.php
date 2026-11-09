<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 10mm 18mm 9mm; }
        body { margin: 0; color: #17212c; font: 11pt Arial, sans-serif; }
        .letter-paper { position: relative; width: 100%; min-height: 278mm; margin: 0; padding: 0; background: #fff; font: 11pt Arial, sans-serif; }
        .letter-head { width: 100%; padding: 0 0 8px; border-bottom: 3px double #17212c; text-align: center; }
        .letterhead-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .letterhead-table td { padding: 0; vertical-align: middle; }
        .letterhead-logo { width: 16%; text-align: center; }
        .letterhead-logo img { display: block; position: static; width: 28mm; height: 30mm; margin: 0 auto; object-fit: contain; transform: none; }
        .letter-paper-invitation .letterhead-logo img { width: 28mm; height: 30mm; }
        .letterhead-center { width: 68%; text-align: center; }
        .letter-head strong,.letter-head b,.letter-head small { display: block; line-height: 1.2; }
        .letterhead-center strong,.letterhead-center b,.letterhead-center small { white-space: nowrap; }
        .letter-head strong { font: 400 12pt Arial,sans-serif; letter-spacing: 0; }
        .letter-head b { margin: 1mm; font: 700 13pt Arial,sans-serif; }
        .letterhead-center b:last-of-type { font-size: 14pt; }
        .letter-head small { font-size: 9px; line-height: 1.5; }
        .letter-meta { position: relative; display: table; width: 100%; margin: 5mm 0 6mm; font-size: 11pt; line-height: 1.35; }
        .letter-meta-fields { display: table-cell; width: 60%; }
        .letter-meta-date { display: table-cell; width: 40%; text-align: right; white-space: nowrap; }
        .letter-meta-fields div { margin-bottom: 1mm; }
        .letter-paper-invitation .letter-meta-table { width: auto; margin: 0; border-collapse: collapse; table-layout: fixed; font: inherit; }
        .letter-paper-invitation .letter-meta-table td { padding: 0; border: 0; background: transparent; vertical-align: top; line-height: 1.35; }
        .letter-paper-invitation .letter-meta-table td:first-child { width: 21mm; white-space: nowrap; }
        .letter-paper-invitation .letter-meta-table td:nth-child(2) { width: 4mm; text-align: center; }
        .letter-paper-invitation .letter-meta-table td:last-child { overflow-wrap: anywhere; }
        .letter-paper-invitation .letter-subject { display: none; }
        .letter-address { font-size: 11pt; line-height: 1.4; }
        .letter-subject { margin: 5mm 0 2mm; font-size: 11pt; }
        .letter-body { margin: 2mm 0; min-height: 0; font-size: 11pt; line-height: 1.4; white-space: pre-wrap; }
        .invitation-opening { margin-top: 8mm; text-indent: 10mm; }
        .invitation-closing { margin-top: 6mm; }
        .event-info { width: auto; min-width: 0; margin: 4mm 0 2mm 14mm; border-collapse: collapse; }
        .event-info td { padding: 1mm 2mm 1mm 0; border: 0; font-size: 11pt; line-height: 1.45; }
        .event-info td:first-child { width: 28mm; white-space: nowrap; }
        .letter-document-title,.letter-document-subtitle { text-align: center; font-size: 11pt; font-weight: 400; text-decoration: none; }
        .letter-document-title { margin: 5mm 0 3mm; }
        .letter-document-subtitle { margin: 4mm 0; }
        .task-letter-number,.mutation-letter-number { margin: 0 0 5mm; text-align: center; font-size: 10pt; }
        .letter-fields { margin: 4mm 0; line-height: 1.5; }
        .letter-field-label { display: inline-block; min-width: 36mm; }
        .letter-field-colon { display: inline-block; width: 8mm; text-align: center; }
        .letter-field-value { display: inline; }
        .letter-fields-pdf { width: 164mm; margin: 2.5mm 0 2.5mm 10mm; border-collapse: collapse; table-layout: fixed; font: 9.5pt/1.3 Arial,sans-serif; }
        .letter-fields-pdf td { padding: 0; border: 0; background: transparent; vertical-align: top; }
        .letter-fields-pdf td:last-child { overflow-wrap: anywhere; word-wrap: break-word; word-break: break-word; }
        .letter-fields-pdf td:first-child { width: 20%; }
        .letter-fields-pdf td:nth-child(2) { width: 3%; text-align: center; }
        .letter-fields-pdf .letter-field-heading td { padding-top: 3mm; padding-bottom: 1mm; }
        .letter-paper-compact .letter-body { margin: 2mm 0; font-size: 10pt; line-height: 1.4; }
        .letter-paper-compact .mutation-documents { margin: 1mm 0 1.5mm 18mm; padding: 0; list-style: none; font-size: 9.5pt; line-height: 1.2; }
        .letter-paper-compact .mutation-documents li { position: relative; padding-left: 5mm; }
        .letter-paper-compact .mutation-documents li:before { position: absolute; left: 0; content: "-"; }
        .letter-paper-compact .mutation-follow-up,.letter-paper-compact .letter-closing { margin: 1.5mm 0; font-size: 9.5pt; line-height: 1.35; }
        .letter-paper-compact .signature { width: 100%; margin: 0; font-size: 9pt; line-height: 1.3; }
        .letter-paper-compact .signature b { margin-top: 7mm; font-weight: 400; }
        .letter-person-fields { margin-left: 41mm; }
        .letter-person-fields div { display: table; width: 100%; margin: 1mm 0; }
        .letter-person-fields span { display: table-cell; }
        .letter-person-fields span:first-child { width: 28mm; }
        .letter-person-fields .letter-field-colon { width: 8mm; }
        .task-letter-basis { width: 100%; margin: 3mm 0 5mm; border-collapse: collapse; table-layout: fixed; font: 10pt/1.35 Arial,sans-serif; }
        .task-letter-basis td { padding: 0; vertical-align: top; }
        .task-letter-basis td:nth-child(2) { text-align: center; }
        .task-letter-basis td:last-child { white-space: pre-wrap; text-align: justify; text-justify: inter-word; overflow-wrap: anywhere; word-wrap: break-word; }
        .letter-task-layout { width: 100%; margin: 2mm 0 4mm; border-collapse: collapse; table-layout: fixed; font: 10pt/1.3 Arial,sans-serif; }
        .letter-task-layout > tbody > tr > td { padding: 0; vertical-align: top; }
        .letter-task-layout td { min-width: 0; }
        .letter-task-layout > tbody > tr + tr > td { padding-top: 0; padding-bottom: 0; }
        .letter-task-layout > tbody + tbody > tr > td { padding-top: 3mm; padding-bottom: 3mm; }
        .letter-task-layout td:nth-child(2),.letter-task-layout td:nth-child(5) { text-align: center; }
        .letter-task-layout td:nth-child(4) { white-space: nowrap; }
        .letter-task-layout td:nth-child(6) { overflow-wrap: anywhere; word-wrap: break-word; word-break: break-word; }
        .task-assignment-text { display: block; box-sizing: border-box; max-width: 100%; white-space: pre-wrap; text-align: justify; text-justify: inter-word; overflow-wrap: anywhere; word-wrap: break-word; word-break: break-word; }
        .letter-task-layout td:nth-child(5) { text-align: center; }
        .letter-closing { margin: 5mm 0; font-size: 10pt; line-height: 1.4; }
        .outgoing-signature-block { width: 68mm; margin: 5mm 0 0 auto; font-size: 10pt; line-height: 1.4; }
        .mutation-signature-date,.task-signature-meta { margin-bottom: 2mm; text-align: center; }
        .task-signature-meta { text-align: left; }
        .task-signature-meta div { margin: 1mm 0; }
        .task-signature-meta span:first-child { display: inline-block; min-width: 28mm; text-align: left; }
        .signature { width: 100%; margin: 5mm 12mm 0 auto; text-align: left; font-size: 10pt; line-height: 1.4; }
        .signature-space { display: block; height: 8mm; }
        .signature b { display: block; margin-top: 0; font-weight: 400; text-decoration: underline; }
        .outgoing-signature-block .signature { margin: 0; }
        .outgoing-signature-block .signature b { margin-top: 8mm; }
        .letter-paper-invitation .invitation-signature { margin-top: 5mm; }
        .letter-paper-invitation .invitation-signature .signature-space { height: 14mm; }
        .letter-paper-invitation .signature b { text-decoration: none; }
        .mutation-documents { margin: 2mm 0 2mm 14mm; }
        .task-participant-table { width: 100%; margin: 3mm 0; border-collapse: collapse; table-layout: fixed; font-size: 9.5pt; line-height: 1.2; }
        .task-participant-table th,.task-participant-table td { padding: 1mm !important; text-align: left !important; vertical-align: top; font-weight: 400; }
        .task-participant-table th:first-child,.task-participant-table td:first-child { padding-left: 0; }
        .task-participant-table th:nth-child(2),.task-participant-table td:nth-child(2) { overflow-wrap: anywhere; word-wrap: break-word; word-break: break-word; }
        .draft-watermark { position: absolute; top: 50%; left: 50%; color: rgba(200,30,30,.16); font-size: 128pt; font-weight: bold; letter-spacing: .08em; transform: translate(-50%,-50%) rotate(-30deg); white-space: nowrap; }
        @if(!empty($screenPreview))
            @media screen {
                html { min-height: 100%; background: #e9eef2; }
                body { min-height: 100%; padding: 16px 0; background: #e9eef2; font-size: 12pt; }
                .letter-paper { box-sizing: border-box; width: 210mm; max-width: calc(100vw - 32px); min-height: 297mm; height: auto; margin: 0 auto; padding: 10mm 18mm 9mm; background: #fff; box-shadow: 0 4px 18px #182b4220; font-size: 12pt; }
                .letter-task-layout,.task-letter-basis { font-size: 11pt; line-height: 1.4; }
                .task-participant-table { font-size: 10.5pt; line-height: 1.3; }
            }
            @media screen and (max-width: 700px) {
                body { padding: 8px 0; }
                .letter-paper { max-width: calc(100vw - 16px); padding: 8mm 6mm 9mm; }
                .letterhead-logo img,.letter-paper-invitation .letterhead-logo img { width: 16mm; height: 18mm; }
                .letter-head strong { font-size: 9pt; }
                .letter-head b { font-size: 10pt; }
                .letterhead-center b:last-of-type { font-size: 11pt; }
                .letter-head small { font-size: 7px; }
            }
        @endif
    </style>
</head>
<body>
    @php($pdfMode = true)
    @include('outgoing.document', [
        'outgoing' => $outgoing,
        'pdfMode' => true,
        'logoKepri' => $logoKepri,
        'logoSmkn4' => $logoSmkn4,
    ])
</body>
</html>
