<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratinjau {{ $outgoing->subject }}</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ hash_file('sha256', public_path('app.css')) }}">
    <style>
        html,body{min-height:100%;margin:0}
        body{padding:16px;background:#e9eef2}
        @media print{body{padding:0;background:#fff}.letter-paper{margin:0 auto;box-shadow:none}}
    </style>
</head>
<body>
    @include('outgoing.document', ['outgoing' => $outgoing])
</body>
</html>
