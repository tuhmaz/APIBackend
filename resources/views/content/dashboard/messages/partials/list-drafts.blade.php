@if(isset($draftMessages) && $draftMessages->count())
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>{{ __('Subject') }}</th>
          <th>{{ __('Preview') }}</th>
          <th>{{ __('Updated At') }}</th>
          <th class="text-end">{{ __('Actions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($draftMessages as $message)
          <tr>
            <td class="fw-medium">{{ $message->subject ?? __('(No subject)') }}</td>
            <td class="text-truncate" style="max-width: 400px;">{{ \Illuminate\Support\Str::limit($message->body, 120) }}</td>
            <td>{{ optional($message->updated_at)->format('Y-m-d H:i') }}</td>
            <td class="text-end">
              <a href="{{ route('dashboard.messages.compose', ['draft' => $message->id]) }}" class="btn btn-sm btn-outline-primary">
                <i class="massag-icon ti tabler-pencil"></i> {{ __('Edit') }}
              </a>
              <form action="{{ route('dashboard.messages.delete', $message->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                  <i class="massag-icon ti tabler-trash"></i> {{ __('Delete') }}
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@else
  @include('content.dashboard.messages.partials.empty-state', [
    'icon' => 'tabler-file',
    'title' => __('No draft messages found.'),
    'subtitle' => null
  ])
@endif
