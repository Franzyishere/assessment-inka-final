@props([
    'title' => 'INKA Assessment Portal',
    'description' => 'INKA Assessment Portal — Assess. Develop. Grow. Platform asesmen online PT INKA untuk mengenali potensi, mengembangkan kompetensi, dan membangun talenta masa depan INKA.',
    'image' => null,
])

@php
    $siteName = 'INKA Assessment Portal';
    $metaTitle = $title === $siteName ? $siteName : $title.' | '.$siteName;
    $previewPath = 'images/backgrounds/gedung-inka-login.jpg';
    $metaImage = $image ?: asset($previewPath).'?v='.filemtime(public_path($previewPath));
    $previewSize = $image ? null : getimagesize(public_path($previewPath));
    $metaUrl = route('login');
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
@if(str_starts_with($metaImage, 'https://'))
<meta property="og:image:secure_url" content="{{ $metaImage }}">
@endif
@if($previewSize)
<meta property="og:image:type" content="{{ $previewSize['mime'] }}">
<meta property="og:image:width" content="{{ $previewSize[0] }}">
<meta property="og:image:height" content="{{ $previewSize[1] }}">
@endif
<meta property="og:image:alt" content="Gedung PT INKA — halaman masuk INKA Assessment Portal">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $metaImage }}">
<meta name="twitter:image:alt" content="Gedung PT INKA — halaman masuk INKA Assessment Portal">
