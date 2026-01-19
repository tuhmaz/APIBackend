@props([
    'desktopKey' => null,
    'mobileKey' => null,
    'class' => '',
    'label' => __('إعلان'),
    'minHeightDesktop' => '120px',
    'minHeightMobile' => '250px',
])

@php
    $detect = new \Detection\MobileDetect();
    $isMobile = $detect->isMobile();

    $keyForDevice = null;
    if ($isMobile) {
        $keyForDevice = $mobileKey ?: $desktopKey;
    } else {
        $keyForDevice = $desktopKey ?: $mobileKey;
    }

    $rawSnippet = $keyForDevice ? trim((string) config("settings.$keyForDevice")) : null;
    $minHeight = $isMobile ? $minHeightMobile : $minHeightDesktop;
    $sanitizedSnippet = null;

    if ($rawSnippet !== null && $rawSnippet !== '') {
        try {
            $sanitizedSnippet = \App\Support\AdSnippetSanitizer::sanitize(
                $rawSnippet,
                config('settings.adsense_client'),
                $keyForDevice ?? 'adsense_banner'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AdSense snippet skipped due to sanitizer rejection', [
                'key' => $keyForDevice,
                'error' => $e->getMessage(),
            ]);
            $sanitizedSnippet = null;
        }
    }

    $shouldRender = filled($sanitizedSnippet);
@endphp

@if($shouldRender)
  <div {{ $attributes->class(['adsense-banner', $class])->merge(['role' => 'complementary', 'aria-label' => 'Advertisement']) }}>
    {{-- Label removed to comply with AdSense policies - no text above ads allowed --}}
    <div class="adsense-banner__slot" style="min-height: {{ $minHeight }};">
      {!! $sanitizedSnippet !!}
    </div>
  </div>
@endif
