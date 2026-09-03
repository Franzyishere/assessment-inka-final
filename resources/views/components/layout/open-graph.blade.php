@props([
    'title' => 'INKA Talent Management System',
    'description' => 'Portal terintegrasi PT Industri Kereta Api (Persero) untuk layanan Assessment dan Recruitment.',
    'image' => null,
])

@php
    $siteName = 'INKA Talent Management System';
    $metaTitle = $title === $siteName ? $siteName : $title.' | '.$siteName;
    $metaImage = $image ?: asset('images/logo/imagesinka.png');
    $metaUrl = url()->current();
@endphp

<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $metaUrl }}">

<meta property="og:locale" content="id_ID">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $metaUrl }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:secure_url" content="{{ $metaImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="423">
<meta property="og:image:height" content="133">
<meta property="og:image:alt" content="Logo PT Industri Kereta Api (Persero)">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $metaImage }}">
<meta name="twitter:image:alt" content="Logo PT Industri Kereta Api (Persero)">
