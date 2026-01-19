@php
  // Props: src, width, height, alt, class, style, sizes, lazy(bool), fetchpriority, decoding
  $src = $src ?? null;
  $width = (int)($width ?? 0);
  $height = (int)($height ?? 0);
  $alt = $alt ?? '';
  $class = $class ?? '';
  $style = $style ?? '';
  $sizes = $sizes ?? ($width ? $width.'px' : '');
  $lazy = isset($lazy) ? filter_var($lazy, FILTER_VALIDATE_BOOLEAN) : true;
  $fetchpriority = $fetchpriority ?? 'auto';
  $decoding = $decoding ?? 'async';

  $srcset = null; $src1x = $src; $src2x = null;
  if ($src && $width && $height) {
    [$thumb1x, $thumb2x] = \App\Helpers\Helpers::buildThumbs($src, $width.'x'.$height);
    if ($thumb1x) {
      $src1x = $thumb1x; $src2x = $thumb2x;
    }
  }
  if ($src2x) {
    $srcset = $src1x.' 1x, '.$src2x.' 2x';
  }
@endphp
<img
  src="{{ $src1x }}"
  @if($srcset) srcset="{{ $srcset }}" @endif
  @if($sizes) sizes="{{ $sizes }}" @endif
  width="{{ $width }}"
  height="{{ $height }}"
  alt="{{ $alt }}"
  class="{{ $class }}"
  style="{{ $style }}"
  @if($lazy) loading="lazy" @endif
  decoding="{{ $decoding }}"
  fetchpriority="{{ $fetchpriority }}"
/>
