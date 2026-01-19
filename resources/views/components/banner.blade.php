@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

@php
  $styleClass = match($style) {
    'success' => 'bg-success',
    'danger' => 'bg-danger',
    default => 'bg-secondary'
  };
@endphp

<div id="app-banner" class="alert alert-banner mb-0 rounded-0 {{ $styleClass }}" role="alert"
     style="display: {{ $message ? 'block' : 'none' }};">
  <div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <span id="app-banner-badge" class="badge rounded-pill py-2 {{ $style === 'success' ? 'bg-success' : ($style === 'danger' ? 'bg-danger' : 'bg-secondary') }}">
        <svg id="icon-success" style="display: {{ $style === 'success' ? 'inline' : 'none' }};" class="h-px-20 w-px-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg id="icon-danger" style="display: {{ $style === 'danger' ? 'inline' : 'none' }};" class="h-px-20 w-px-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <svg id="icon-info" style="display: {{ ($style !== 'success' && $style !== 'danger') ? 'inline' : 'none' }};" class="h-px-20 w-px-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
      </span>
      <span id="app-banner-message" class="text-white">{{ $message }}</span>
    </div>

    <div class="text-end">
      <button type="button" class="btn btn-icon btn-sm {{ $style === 'success' ? 'btn-success' : ($style === 'danger' ? 'btn-danger' : 'btn-secondary') }}" aria-label="Dismiss" onclick="(function(){
        var el = document.getElementById('app-banner'); if (el) el.style.display='none';
      })()">
        <svg class="h-px-20 w-px-20 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</div>

<script>
  // Banner message listener without Alpine (CSP-safe)
  document.addEventListener('banner-message', function (event) {
    try {
      var style = event.detail?.style || 'secondary';
      var message = event.detail?.message || '';
      var banner = document.getElementById('app-banner');
      var badge = document.getElementById('app-banner-badge');
      var msgEl = document.getElementById('app-banner-message');
      var iconSuccess = document.getElementById('icon-success');
      var iconDanger = document.getElementById('icon-danger');
      var iconInfo = document.getElementById('icon-info');

      if (!banner || !badge || !msgEl) return;

      // Update background classes
      banner.classList.remove('bg-success','bg-danger','bg-secondary');
      badge.classList.remove('bg-success','bg-danger','bg-secondary');

      if (style === 'success') {
        banner.classList.add('bg-success');
        badge.classList.add('bg-success');
      } else if (style === 'danger') {
        banner.classList.add('bg-danger');
        badge.classList.add('bg-danger');
      } else {
        banner.classList.add('bg-secondary');
        badge.classList.add('bg-secondary');
      }

      // Toggle icons
      if (iconSuccess && iconDanger && iconInfo) {
        iconSuccess.style.display = style === 'success' ? 'inline' : 'none';
        iconDanger.style.display = style === 'danger' ? 'inline' : 'none';
        iconInfo.style.display = (style !== 'success' && style !== 'danger') ? 'inline' : 'none';
      }

      // Update message and show
      msgEl.textContent = message || '';
      banner.style.display = message ? 'block' : 'none';
    } catch (e) {
      console.warn('Banner update failed:', e);
    }
  });
</script>
