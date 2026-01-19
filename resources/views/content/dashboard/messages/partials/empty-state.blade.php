<div class="text-center p-5">
  <i class="massag-icon ti {{ $icon ?? 'tabler-inbox' }} mb-2" style="font-size: 3rem;"></i>
  <h5>{{ $title ?? __('Nothing here') }}</h5>
  @if(!empty($subtitle))
    <p class="mb-0">{{ $subtitle }}</p>
  @endif
</div>
