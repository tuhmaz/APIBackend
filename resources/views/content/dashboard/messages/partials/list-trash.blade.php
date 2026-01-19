<h6 class="mb-3">{{ __('Unread Messages') }}</h6>
@if(isset($unreadMessages) && $unreadMessages->count())
  <div class="table-responsive mb-4">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('Subject') }}</th>
          <th>{{ __('Preview') }}</th>
          <th>{{ __('Received At') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($unreadMessages as $message)
          <tr>
            <td class="fw-medium">{{ $message->subject ?? __('(No subject)') }}</td>
            <td class="text-truncate" style="max-width: 400px;">{{ \Illuminate\Support\Str::limit($message->body, 120) }}</td>
            <td>{{ optional($message->created_at)->format('Y-m-d H:i') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <p class="text-muted">{{ __('No unread messages.') }}</p>
@endif

<h6 class="mb-3">{{ __('Sent Messages') }}</h6>
@if(isset($sentMessages) && $sentMessages->count())
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('Subject') }}</th>
          <th>{{ __('Preview') }}</th>
          <th>{{ __('Updated At') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sentMessages as $message)
          <tr>
            <td class="fw-medium">{{ $message->subject ?? __('(No subject)') }}</td>
            <td class="text-truncate" style="max-width: 400px;">{{ \Illuminate\Support\Str::limit($message->body, 120) }}</td>
            <td>{{ optional($message->updated_at)->format('Y-m-d H:i') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  <p class="text-muted">{{ __('No sent messages.') }}</p>
@endif
