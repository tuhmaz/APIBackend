<div class="email-list-item d-flex align-items-center bg-lighter px-3 py-2">
  <div class="email-list-item-content ms-2 ms-sm-4 me-2 w-100">
    <div class="input-group input-group-merge">
      <span class="input-group-text" id="basic-addon1"><i class="massag-icon ti tabler-search"></i></span>
      <input type="text" class="form-control email-search" placeholder="{{ __('Search mail') }}">
    </div>
  </div>
</div>
<hr class="my-0">

@forelse($messages as $message)
  <div class="email-list-item d-flex align-items-center {{ $message->read ? '' : 'email-unread' }}"
       data-message-id="{{ $message->id }}"
       data-bs-toggle="modal"
       data-bs-target="#messageModal{{ $message->id }}">
    <div class="email-list-item-content ms-2 ms-sm-4 me-2">
      <span class="email-list-item-username me-2 h6">{{ $message->sender->name }}</span>
      <span class="email-list-item-subject d-xl-inline-block d-block">
        {{ $message->subject }}
      </span>
    </div>
    <div class="email-list-item-meta ms-auto d-flex align-items-center">
      <span class="email-list-item-time">{{ $message->created_at->format('M d') }}</span>
      <div class="ms-3">
        @if($message->is_important)
          <i class="massag-icon ti tabler-star text-warning"></i>
        @endif
      </div>
    </div>
  </div>
  <hr class="my-0">
@empty
  @include('content.dashboard.messages.partials.empty-state', [
    'icon' => 'tabler-inbox',
    'title' => __('Your inbox is empty'),
    'subtitle' => __('No messages found')
  ])
@endforelse

@if(method_exists($messages, 'links'))
  <div class="d-flex justify-content-center my-3">
    {{ $messages->withQueryString()->links('components.pagination.custom') }}
  </div>
@endif

{{-- Per-page Message Modals --}}
@foreach($messages as $message)
  <div class="modal fade message-modal" id="messageModal{{ $message->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="massag-icon ti tabler-mail me-2"></i>
            {{ $message->subject }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="message-info d-flex align-items-center">
            <div class="d-flex align-items-center flex-grow-1">
              <img src="{{ $message->sender->getAvatarUrl() }}" alt="{{ $message->sender->name }}" class="sender-avatar">
              <div>
                <h6 class="sender-name mb-0">{{ $message->sender->name }}</h6>
                <div class="message-timestamp">
                  <i class="massag-icon ti tabler-calendar me-1"></i>
                  {{ $message->created_at->format('Y-m-d') }}
                  <i class="massag-icon ti tabler-clock ms-2 me-1"></i>
                  {{ $message->created_at->format('H:i') }}
                </div>
              </div>
            </div>
            @if($message->is_important)
              <div class="ms-auto">
                <span class="badge bg-warning">
                  <i class="massag-icon ti tabler-star me-1"></i>
                  {{ __('Important') }}
                </span>
              </div>
            @endif
          </div>
          <div class="message-content">
            {!! $message->body !!}
          </div>

          <div class="message-reply">
            <div class="message-reply-header">
              <span class="message-reply-toggle">
                <i class="massag-icon ti tabler-arrow-back-up me-1"></i>
                {{ __('Quick Reply') }}
              </span>
            </div>
            <div class="message-reply-form">
              <form class="quick-reply-form" action="{{ route('dashboard.messages.send') }}" method="POST">
                @csrf
                <input type="hidden" name="recipient" value="{{ $message->sender_id }}">
                <input type="hidden" name="subject" value="Re: {{ $message->subject }}">
                <div id="editor{{ $message->id }}" class="message-reply-editor"></div>
                <input type="hidden" name="message" class="quick-reply-message-input" value="">
                <div class="invalid-feedback d-block quick-reply-error" style="display:none;">
                  {{ __('The message field is required.') }}
                </div>
                <div class="text-end mt-3">
                  <button type="submit" class="btn btn-primary quick-reply-btn">
                    <i class="massag-icon ti tabler-send me-1"></i>
                    {{ __('Send Reply') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="massag-icon ti tabler-x me-1"></i>
            {{ __('Close') }}
          </button>
          <button type="button" class="btn btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $message->id }}">
            <i class="massag-icon ti tabler-trash me-1"></i>
            {{ __('Delete') }}
          </button>
          <a href="{{ route('dashboard.messages.compose', ['reply_to' => $message->id]) }}"
             class="btn btn-primary btn-reply">
            <i class="massag-icon ti tabler-edit me-1"></i>
            {{ __('Full Reply') }}
          </a>
        </div>
      </div>
    </div>
  </div>
@endforeach

{{-- Per-page Delete Confirmation Modals --}}
@foreach($messages as $message)
  <div class="modal modal-danger fade" id="deleteModal{{ $message->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm">
      <div class="modal-content">
        <div class="modal-header flex-column">
          <div class="icon-box">
            <i class="massag-icon ti tabler-alert-triangle text-warning" style="font-size: 3rem;"></i>
          </div>
          <h4 class="modal-title w-100 text-center mt-2">{{ __('Confirm Delete') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center p-4">
          <p class="mb-0">{{ __('Are you sure you want to delete this message?') }}</p>
          <p class="text-muted small">{{ __('This action cannot be undone.') }}</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
            <i class="massag-icon ti tabler-x me-1"></i>
            {{ __('Cancel') }}
          </button>
          <button type="button" class="btn btn-danger px-4 confirm-delete" data-message-id="{{ $message->id }}">
            <i class="massag-icon ti tabler-trash me-1"></i>
            {{ __('Delete') }}
          </button>
        </div>
      </div>
    </div>
  </div>
@endforeach
