<div class="email-list-item d-flex align-items-center bg-lighter px-3 py-2">
  <div class="email-list-item-content ms-2 ms-sm-4 me-2 w-100">
    <div class="input-group input-group-merge">
      <span class="input-group-text" id="basic-addon1"><i class="massag-icon ti tabler-search"></i></span>
      <input type="text" class="form-control email-search" placeholder="{{ __('Search sent mail') }}">
    </div>
  </div>
</div>
<hr class="my-0">

@forelse($sentMessages as $message)
  <div class="email-list-item d-flex align-items-center">
    <div class="email-list-item-content ms-2 ms-sm-4 me-2">
      <span class="email-list-item-username me-2 h6">
        {{ $message->conversation->user1_id == auth()->id()
           ? $message->conversation->user2->name
           : $message->conversation->user1->name }}
      </span>
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
      <form action="{{ route('dashboard.messages.delete', $message->id) }}" method="POST" class="ms-3">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('{{ __('Are you sure you want to delete this message?') }}')">
          <i class="massag-icon ti tabler-trash"></i>
        </button>
      </form>
    </div>
  </div>
  <hr class="my-0">
@empty
  @include('content.dashboard.messages.partials.empty-state', [
    'icon' => 'tabler-send',
    'title' => __('No sent messages'),
    'subtitle' => __('Start sending messages to see them here')
  ])
@endforelse
