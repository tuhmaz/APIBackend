<div class="col-12 col-lg-3 email-navigation border-end">
  <div class="d-grid gap-2 p-4">
    <a href="{{ route('dashboard.messages.compose') }}"
       class="btn btn-primary btn-lg waves-effect waves-light compose-trigger"
       data-compose-trigger="1"
       data-bs-toggle="modal"
       data-bs-target="#composeModal">
      <i class="massag-icon ti tabler-plus me-1"></i>{{ __('Compose') }}
    </a>
  </div>
  <div class="email-navigation-list ps ps--active-y">
    <a href="{{ route('dashboard.messages.index') }}" class="d-flex align-items-center navigation-item {{ request()->routeIs('dashboard.messages.index') ? 'active' : '' }}">
      <i class="massag-icon ti tabler-mail me-2"></i>
      <span>{{ __('Inbox') }}</span>
      @isset($unreadMessagesCount)
        @if($unreadMessagesCount > 0)
          <span class="badge bg-label-primary rounded-pill ms-auto">{{ $unreadMessagesCount }}</span>
        @endif
      @endisset
    </a>
    <a href="{{ route('dashboard.messages.sent') }}" class="d-flex align-items-center navigation-item {{ request()->routeIs('dashboard.messages.sent') ? 'active' : '' }}">
      <i class="massag-icon ti tabler-send me-2"></i>
      <span>{{ __('Sent') }}</span>
      @isset($sentMessagesCount)
        @if($sentMessagesCount > 0)
          <span class="badge bg-label-secondary rounded-pill ms-auto">{{ $sentMessagesCount }}</span>
        @endif
      @endisset
    </a>
    <a href="{{ route('dashboard.messages.drafts') }}" class="d-flex align-items-center navigation-item {{ request()->routeIs('dashboard.messages.drafts') ? 'active' : '' }}">
      <i class="massag-icon ti tabler-file me-2"></i>
      <span>{{ __('Drafts') }}</span>
    </a>
    <a href="{{ route('dashboard.messages.trash') }}" class="d-flex align-items-center navigation-item {{ request()->routeIs('dashboard.messages.trash') ? 'active' : '' }}">
      <i class="massag-icon ti tabler-trash me-2"></i>
      <span>{{ __('Trash') }}</span>
    </a>
  </div>
</div>
