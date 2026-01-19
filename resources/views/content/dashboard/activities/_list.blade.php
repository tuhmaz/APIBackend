@php
use Illuminate\Support\Str;
$defaultAvatar = 'assets/img/avatars/default.png';
@endphp

<ul class="timeline timeline-center mt-3">
    @forelse($activities as $idx => $activity)
        @php
          $badgeClass = match($activity['type']) {
            'article' => 'bg-primary',
            'news' => 'bg-info',
            'comment' => 'bg-warning',
            'user' => 'bg-success',
            default => 'bg-secondary',
          };
          $collapseId = 'activity-details-' . $idx . '-' . ($activity['time']->timestamp ?? time());
          $propsJson = json_encode($activity['properties'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        @endphp
        <li class="timeline-item">
            <span class="timeline-indicator {{ $badgeClass }}">
                <i class="bx {{ $activity['icon'] }}"></i>
            </span>
            <div class="timeline-event">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0">{{ $activity['action'] }}</h6>
                            <span class="badge rounded-pill {{ $badgeClass }} text-uppercase">{{ $activity['type'] }}</span>
                        </div>
                        <p class="mb-2 text-muted small">{{ Str::limit($activity['description'] ?? '', 160) }}</p>
                    </div>
                    <small class="text-muted">{{ $activity['time']->diffForHumans() }}</small>
                </div>

                <div class="d-flex align-items-center mb-2">
                    @if(!empty($activity['user_profile_url']))
                        <a href="{{ $activity['user_profile_url'] }}" class="d-inline-flex align-items-center text-decoration-none">
                            <img src="{{ $activity['user_avatar'] ?? asset($defaultAvatar) }}"
                                 class="rounded-circle me-2"
                                 width="28"
                                 height="28"
                                 alt="{{ $activity['user'] }}">
                            <small class="fw-medium">{{ $activity['user'] }}</small>
                        </a>
                    @else
                        <img src="{{ $activity['user_avatar'] ?? asset($defaultAvatar) }}"
                             class="rounded-circle me-2"
                             width="28"
                             height="28"
                             alt="{{ $activity['user'] }}">
                        <small class="fw-medium">{{ $activity['user'] }}</small>
                    @endif
                </div>

                @if(!empty($activity['properties']))
                <div>
                    @php $commentUrl = data_get($activity, 'properties.url'); @endphp
                    @if(!empty($commentUrl))
                        <a href="{{ $commentUrl }}" class="btn btn-sm btn-primary me-2" target="_blank" rel="noopener">
                            <i class="bx bx-comment-detail me-1"></i>{{ __('View Comment') }}
                        </a>
                    @endif
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                        <i class="bx bx-code-alt me-1"></i>{{ __('Details') }}
                    </button>
                    <div class="collapse mt-2" id="{{ $collapseId }}">
                        <div class="card card-body bg-light border-0">
                            <pre class="mb-0 small text-muted">{{ $propsJson }}</pre>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </li>
    @empty
        <li class="text-center py-4">
            <p class="mb-0">لا توجد نشاطات حديثة</p>
        </li>
    @endforelse
</ul>
