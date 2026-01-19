<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\Helpers\Helpers;

  $menuFixed =
      $configData['layout'] === 'vertical'
          ? $menuFixed ?? ''
          : ($configData['layout'] === 'front'
              ? ''
              : $configData['headerType']);
  $navbarType =
      $configData['layout'] === 'vertical'
          ? $configData['navbarType']
          : ($configData['layout'] === 'front'
              ? 'layout-navbar-fixed'
              : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  // Content layout per container
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

  // Favicon from settings with fallback
  $favicon = config('settings.site_favicon')
      ? asset('storage/' . config('settings.site_favicon'))
      : asset('assets/img/favicon/favicon.ico');

  // Get skin name from configData - only applies to admin layouts
  $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
  $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

  // Get semiDark value from configData - only applies to admin layouts
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);

  // Generate primary color CSS if color is set
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }

  // Determine if any AdSense unit is configured on the public side
  $adsenseClient = config('settings.adsense_client');
  $adsKeys = [
      'google_ads_desktop_home',
      'google_ads_desktop_home_2',
      'google_ads_desktop_classes',
      'google_ads_desktop_classes_2',
      'google_ads_desktop_subject',
      'google_ads_desktop_article',
      'google_ads_desktop_article_2',
      'google_ads_desktop_news',
      'google_ads_desktop_news_2',
      'google_ads_desktop_download',
      'google_ads_desktop_download_2',
      'google_ads_mobile_home',
      'google_ads_mobile_home_2',
      'google_ads_mobile_classes',
      'google_ads_mobile_classes_2',
      'google_ads_mobile_subject',
      'google_ads_mobile_article',
      'google_ads_mobile_article_2',
      'google_ads_mobile_news',
      'google_ads_mobile_news_2',
      'google_ads_mobile_download',
      'google_ads_mobile_download_2',
  ];

  $adsConfigured = collect($adsKeys)->contains(function ($key) {
      $value = config("settings.$key");

      return filled($value);
  });

  $shouldLoadAdsense = !$isAdminLayout && $adsenseClient && $adsConfigured;
  $cookieConsentEnabled = config('cookie-consent.enabled', true);
  $cookieConsentName = config('cookie-consent.cookie_name', 'laravel_cookie_consent');
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $configData['style'] ?? '' }}-style {{ $contentLayout ?? '' }} {{ $navbarType ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] ?? 'ltr' }}" data-theme="{{ $configData['theme'] ?? 'light' }}"
  data-template="{{ ($configData['layout'] ?? 'vertical') . '-menu-' . ($configData['themeOpt'] ?? 'default') . '-' . ($configData['styleOpt'] ?? 'default') }}"
  data-style="{{ $configData['styleOptVal'] ?? '' }}" data-skin="{{ $skinName }}"
  data-bs-theme="{{ $configData['theme'] ?? 'light' }}" data-assets-path="{{ asset('assets') }}/"
  data-base-url="{{ url('/') }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
  <title>
    @hasSection('meta_title')
      @yield('meta_title')
    @else
      @yield('title')
      {{ config('settings.site_name') ? config('settings.site_name') : 'site_name' }}-{{ config('settings.meta_description', '') }}
    @endif
  </title>
  <link rel="icon" type="image/x-icon" href="{{ $favicon }}" />
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @if (isset($noindex) && $noindex)
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
  @else
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
  @endif

  @if (isset($canonical))
    <link rel="canonical" href="{{ $canonical }}" />
  @else
    <link rel="canonical" href="{{ url()->current() }}" />
  @endif

  @hasSection('meta')
    @yield('meta')
  @else
    @if (request()->is('/'))
      <!-- Primary Meta Tags -->
      <meta name="title" content="{{ config('settings.meta_title', config('settings.site_name', 'Site Name')) }}">
      <meta name="description" content="{{ config('settings.meta_description', '') }}" />
      <meta name="keywords" content="{{ config('settings.meta_keywords', '') }}">

      <!-- Open Graph / Twitter (Homepage only) -->
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:title"
        content="{{ config('settings.meta_title', config('settings.site_name', 'Site Name')) }}" />
      <meta name="twitter:description" content="{{ config('settings.meta_description', '') }}" />
      <meta name="twitter:image" content="{{ asset('storage/' . config('settings.site_logo')) }}" />
      <meta name="twitter:site" content="{{ config('settings.social_twitter') }}" />

      <!-- Open Graph / Facebook (Homepage only) -->
      <meta property="og:type" content="website">
      <meta property="og:url" content="{{ url()->current() }}">
      <meta property="og:title"
        content="{{ config('settings.meta_title', config('settings.site_name', 'Site Name')) }}">
      <meta property="og:description" content="{{ config('settings.meta_description', '') }}">
      <meta property="og:image" content="{{ asset('storage/' . config('settings.site_logo')) }}">
    @endif
  @endif

  @if (config('settings.facebook_pixel_id'))
    <!-- Facebook Pixel Code -->
    <script>
      (function(pixelId){
        function loadFBP(){
          if (window.__fbpLoaded__) return; window.__fbpLoaded__ = true;
          // Preconnect
          var ln = document.createElement('link'); ln.rel='preconnect'; ln.href='https://connect.facebook.net'; ln.crossOrigin='anonymous'; document.head.appendChild(ln);
          // Bootstrap fbq
          !function(f,b,e,v,n,t,s){
            if(f.fbq) return; n=f.fbq=function(){ n.callMethod ? n.callMethod.apply(n,arguments) : n.queue.push(arguments) };
            if(!f._fbq) f._fbq=n; n.push=n; n.loaded=!0; n.version='2.0'; n.queue=[];
            t=b.createElement(e); t.async=!0; t.src=v; s=b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t,s);
          }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
          fbq('init', pixelId);
          // Track after idle to avoid early layout work
          if (window.requestIdleCallback) requestIdleCallback(function(){ fbq('track','PageView'); }, {timeout: 1500});
          else setTimeout(function(){ fbq('track','PageView'); }, 800);
        }
        window.addEventListener('load', function(){
          if (window.requestIdleCallback) requestIdleCallback(loadFBP, {timeout: 1500});
          else setTimeout(loadFBP, 800);
        }, { once: true });
        ['mousedown','keydown','touchstart'].forEach(function(evt){
          window.addEventListener(evt, loadFBP, { once: true, passive: true });
        });
      })('{{ config('settings.facebook_pixel_id') }}');
    </script>
    <noscript>
      <img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ config('settings.facebook_pixel_id') }}&ev=PageView&noscript=1" />
    </noscript>
  @endif

  @if (config('settings.google_analytics_id'))
    <!-- Google Analytics -->
    <script>
      (function(gid){
        function loadGA(){
          if (window.__gaLoaded__) return; window.__gaLoaded__ = true;
          // Preconnect to GTM (no crossorigin since the request is non-CORS)
          var ln = document.createElement('link'); ln.rel='preconnect'; ln.href='https://www.googletagmanager.com'; document.head.appendChild(ln);
          // Load script async
          var s = document.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + gid; document.head.appendChild(s);
          // Init gtag
          window.dataLayer = window.dataLayer || [];
          function gtag(){ dataLayer.push(arguments); }
          window.gtag = gtag;
          gtag('js', new Date());
          // Defer page_view until idle to avoid early layout work
          gtag('config', gid, { send_page_view: false });
          if (window.requestIdleCallback) requestIdleCallback(function(){ gtag('event','page_view'); }, {timeout: 1500});
          else setTimeout(function(){ gtag('event','page_view'); }, 1000);
        }
        // Load after onload/idle, or on first interaction
        window.addEventListener('load', function(){
          if (window.requestIdleCallback) requestIdleCallback(loadGA, {timeout: 1500});
          else setTimeout(loadGA, 800);
        }, { once: true });
        ['mousedown','keydown','touchstart'].forEach(function(evt){
          window.addEventListener(evt, loadGA, { once: true, passive: true });
        });
      })('{{ config('settings.google_analytics_id') }}');
    </script>
  @endif

  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/styles' . $isFront)

  @if($shouldLoadAdsense)
    <!-- Google AdSense: load only after consent -->
    <script>
      (function() {
        var client = @json($adsenseClient);
        var cookieName = @json($cookieConsentName);
        var consentEnabled = @json($cookieConsentEnabled);
        var loaded = false;

        function hasConsent() {
          return document.cookie.split('; ').indexOf(cookieName + '=1') !== -1;
        }

        function loadAdsenseScript() {
          if (loaded) {
            return;
          }
          loaded = true;

          var script = document.createElement('script');
          script.async = true;
          script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(client);
          script.crossOrigin = 'anonymous';
          document.head.appendChild(script);
        }

        if (!consentEnabled) {
          loadAdsenseScript();
          return;
        }

        if (hasConsent()) {
          loadAdsenseScript();
        } else {
          window.addEventListener('cookie-consent:accepted', loadAdsenseScript, { once: true });
        }
      })();
    </script>
  @endif

  @if (
      $primaryColorCSS &&
          (config('custom.custom.primaryColor') ||
              isset($_COOKIE['admin-primaryColor']) ||
              isset($_COOKIE['front-primaryColor'])))
    <!-- Primary Color Style -->
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif

  <!-- AdSense layout helpers - Compliant with Google AdSense policies -->
  <style id="adsense-style">
    .adsense-banner {
      margin: 1.5rem auto;
      text-align: center;
      max-width: 100%;
      overflow: hidden;
    }

    /* Label styles removed to comply with AdSense policies */
    /* No text, arrows, or indicators should point to ads */

    .adsense-banner__slot {
      min-height: 120px;
      display: block;
      width: 100%;
    }

    @media (max-width: 767.98px) {
      .adsense-banner__slot {
        min-height: 250px;
      }
    }
  </style>

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scriptsIncludes' . $isFront)
  @vite(['resources/css/cookie-consent.css', 'resources/css/footer-front.css'])
</head>

<body>
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('layouts/sections/scripts' . $isFront)
  @include('components.cookie-consent')
</body>

</html>
