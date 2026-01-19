@php
$shareUrl = urlencode($url);
$shareTitle = urlencode($title);
@endphp

<div class="social-share-buttons">
    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-facebook btn-icon me-2"
       aria-label="{{ __('Share on Facebook') }}" title="{{ __('Share on Facebook') }}">
        <i class="page-icon ti tabler-brand-facebook" aria-hidden="true"></i>
        <span class="visually-hidden">{{ __('Share on Facebook') }}</span>
    </a>

    <!-- Twitter/X -->
    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-twitter btn-icon me-2"
       aria-label="{{ __('Share on X (Twitter)') }}" title="{{ __('Share on X (Twitter)') }}">
        <i class="page-icon ti tabler-brand-twitter" aria-hidden="true"></i>
        <span class="visually-hidden">{{ __('Share on X (Twitter)') }}</span>
    </a>

    <!-- WhatsApp -->
    <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-success btn-icon me-2"
       aria-label="{{ __('Share on WhatsApp') }}" title="{{ __('Share on WhatsApp') }}">
        <i class="page-icon ti tabler-brand-whatsapp" aria-hidden="true"></i>
        <span class="visually-hidden">{{ __('Share on WhatsApp') }}</span>
    </a>

    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-linkedin btn-icon me-2"
       aria-label="{{ __('Share on LinkedIn') }}" title="{{ __('Share on LinkedIn') }}">
        <i class="page-icon ti tabler-brand-linkedin" aria-hidden="true"></i>
        <span class="visually-hidden">{{ __('Share on LinkedIn') }}</span>
    </a>

    <!-- Copy Link -->
    <button type="button"
            class="btn btn-secondary btn-icon copy-link"
            data-url="{{ $url }}"
            title="{{ __('Copy Link') }}"
            aria-label="{{ __('Copy Link') }}">
        <i class="page-icon ti tabler-copy" aria-hidden="true"></i>
        <span class="visually-hidden">{{ __('Copy Link') }}</span>
    </button>
</div>

@push('scripts')
<script>
document.querySelectorAll('.copy-link').forEach(button => {
    button.addEventListener('click', function() {
        const url = this.getAttribute('data-url');
        navigator.clipboard.writeText(url).then(() => {
            // يمكن إضافة إشعار هنا
            alert('{{ __("Link copied to clipboard!") }}');
        });
    });
});
</script>
@endpush
