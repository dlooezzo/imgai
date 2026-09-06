@php
    $seoService = app(\App\Services\Seo\SeoService::class);
    
    if (isset($page) && $page instanceof \App\Models\Page) {
        $seoMeta = $seoService->resolveForPage($page, $isAdminPreview ?? false);
    } else {
        $seoMeta = $seoService->resolveForTool($activeTool ?? 'overview');
    }

    // Strict noindex safeguard for private/authenticated routes
    if (request()->is('profile*') || request()->is('library*') || request()->is('admin*') || request()->is('auth*') || request()->is('logout*')) {
        $seoMeta['robots'] = 'noindex, nofollow';
    }
@endphp

<!-- Technical SEO Title & Metadata -->
<title>{{ $seoMeta['title'] }}</title>
<meta name="description" content="{{ $seoMeta['description'] }}">
@if(!empty($seoMeta['keywords']))
<meta name="keywords" content="{{ $seoMeta['keywords'] }}">
@endif
<meta name="robots" content="{{ $seoMeta['robots'] }}">
<link rel="canonical" href="{{ $seoMeta['canonical'] }}">

@php
    $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
@endphp
@if(!empty($siteFavicon))
<link rel="icon" href="{{ $siteFavicon }}">
<link rel="apple-touch-icon" href="{{ $siteFavicon }}">
@else
<link rel="icon" href="{{ asset('favicon.ico') }}">
@endif

<!-- Open Graph / Facebook / LinkedIn -->
<meta property="og:title" content="{{ $seoMeta['og']['title'] }}">
<meta property="og:description" content="{{ $seoMeta['og']['description'] }}">
<meta property="og:type" content="{{ $seoMeta['og']['type'] }}">
<meta property="og:url" content="{{ $seoMeta['og']['url'] }}">
@if(!empty($seoMeta['og']['image']))
<meta property="og:image" content="{{ $seoMeta['og']['image'] }}">
@endif
<meta property="og:site_name" content="{{ $seoMeta['og']['site_name'] }}">
<meta property="og:locale" content="{{ $seoMeta['og']['locale'] }}">

<!-- Twitter / X Cards -->
<meta name="twitter:card" content="{{ $seoMeta['twitter']['card'] }}">
<meta name="twitter:title" content="{{ $seoMeta['twitter']['title'] }}">
<meta name="twitter:description" content="{{ $seoMeta['twitter']['description'] }}">
@if(!empty($seoMeta['twitter']['image']))
<meta name="twitter:image" content="{{ $seoMeta['twitter']['image'] }}">
@endif

<!-- Search Engine Verification -->
@if(!empty($seoMeta['verification']['google']))
<meta name="google-site-verification" content="{{ $seoMeta['verification']['google'] }}">
@endif
@if(!empty($seoMeta['verification']['bing']))
<meta name="msvalidate.01" content="{{ $seoMeta['verification']['bing'] }}">
@endif

<!-- JSON-LD Structured Data -->
@if(!empty($seoMeta['structured_data']))
    @foreach ($seoMeta['structured_data'] as $schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
    @endforeach
@endif
