@php
    $host = request()->getHost();

    $enterprise = \App\Http\Models\Enterprise::where('url', $host)->first();

    $ogTitle = 'CRM';
    $ogDescription = 'La información de nuestra empresa';
    $ogImage = asset('assets/enterprises/default/logo.png');

    if ($enterprise) {
        $ogTitle = 'CRM ' . ($enterprise->name ?? '');
        $ogDescription = 'Acceso a la plataforma de gestión de ' . ($enterprise->name ?? '') . '.';

        if (!empty($enterprise->asset_folder)) {
            $ogImage = asset('assets/enterprises/' . $enterprise->asset_folder . '/logos/crmlogo2.png');
        }
    }
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="enterprise-info" content="La información de nuestra empresa">

    <!--Para apps de meta ( WhatsApp, Facebook, Instagram, Linkedin, etc.)-->
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    <!--Para Twitter-->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <meta name="app-version" content="{{ \App\Helpers\ManifestVersion::hash() }}">

    <title>{{ $ogTitle }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $ogImage }}?v={{ rand(0,99999) }}">
</head>
<body>
<div id="app" data-template="style1">
    <Vue></Vue>
</div>
@vite('resources/js/app.js')
<script src="https://accounts.google.com/gsi/client"></script>

<script>
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.tooltip === 'function') {
        window.jQuery(() => {
            window.jQuery('body').tooltip({selector: '[data-toggle=tooltip]', placement: 'bottom', html: true});
        })
    }
</script>
</body>
</html>
