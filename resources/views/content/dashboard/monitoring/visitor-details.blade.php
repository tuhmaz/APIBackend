 @php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Visitor Details'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('Visitor Details') }}</h4>
                <div class="d-flex">
                    <a href="{{ route('dashboard.monitoring.visitors.index') }}" class="btn btn-label-secondary me-2">
                        <i class='tabler-arrow-left me-1'></i> {{ __('Back to Visitors') }}
                    </a>
                    @can('ban ip')
                    <button class="btn btn-danger ban-ip" data-ip="{{ $session->ip }}">
                        <i class='tabler-ban me-1'></i> {{ __('Ban IP') }}
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Visitor Info -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">{{ __('Visitor Information') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    @if($session->user)
                                        @if($session->user->profile_photo_path)
                                            <img src="{{ asset('storage/' . $session->user->profile_photo_path) }}"
                                                 class="rounded-circle me-3" width="60" height="60"
                                                 alt="{{ $session->user->name }}"
                                                 onerror="this.onerror=null; this.src='{{ $session->user->profile_photo_url }}';">
                                        @else
                                            <div class="avatar avatar-lg me-3">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ substr($session->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div>
                                            <h5 class="mb-0">{{ $session->user->name }}</h5>
                                            <span class="text-muted">{{ $session->user->email }}</span>
                                        </div>
                                    @else
                                        <div class="avatar avatar-lg me-3">
                                            <span class="avatar-initial rounded-circle bg-label-secondary">
                                                <i class='tabler-user'></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">{{ __('Guest') }}</h5>
                                            <span class="text-muted">{{ $session->ip }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 mb-3">
                                        <h6 class="text-muted">{{ __('Device & Browser') }}</h6>
                                        <div class="d-flex align-items-center">
                                            @php
                                                // Map to Tabler Icons v2 class names
                                                $browserIcon = match(strtolower($session->browser_family)) {
                                                    'chrome' => 'ti tabler-brand-chrome',
                                                    'firefox' => 'ti tabler-brand-firefox',
                                                    'safari' => 'ti tabler-brand-safari',
                                                    'edge' => 'ti tabler-brand-edge',
                                                    'opera' => 'ti tabler-brand-opera',
                                                    'ie' => 'ti tabler-brand-internet-explorer',
                                                    'yandex' => 'ti tabler-brand-yandex',
                                                    'brave' => 'ti tabler-brand-brave',
                                                    'vivaldi' => 'ti tabler-brand-vivaldi',
                                                    // No dedicated icons for these; fallback to generic browser/world icon
                                                    'samsung internet' => 'ti tabler-browser',
                                                    'uc browser' => 'ti tabler-browser',
                                                    'silk' => 'ti tabler-browser',
                                                    'maxthon' => 'ti tabler-browser',
                                                    'sogou explorer' => 'ti tabler-browser',
                                                    default => 'ti tabler-browser',
                                                };
                                            @endphp
                                            <div class="avatar me-2">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="ti {{ $browserIcon }} icon-base icon-md"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $session->browser }} {{ $session->browser_version }}</div>
                                                <small class="text-muted">{{ $session->platform }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('Device Type') }}</h6>
                                        <div class="d-flex align-items-center">
                                            @if($session->is_mobile)
                                                <i class='tabler-smartphone me-2 text-primary'></i>
                                                <span>{{ __('Mobile') }}</span>
                                            @elseif($session->is_tablet)
                                                <i class='tabler-tablet me-2 text-info'></i>
                                                <span>{{ __('Tablet') }}</span>
                                            @else
                                                <i class='tabler-desktop me-2 text-success'></i>
                                                <span>{{ __('Desktop') }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('IP Address') }}</h6>
                                        <div class="d-flex align-items-center">
                                            <span>{{ $session->ip }}</span>
                                            <button class="btn btn-icon btn-sm btn-label-secondary ms-2" onclick="navigator.clipboard.writeText('{{ $session->ip }}')">
                                                <i class='tabler-copy icon-base ti icon-md'></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="text-muted">{{ __('Location') }}</h6>
                                        <div class="d-flex align-items-center">
                                            @php
                                                // Resolve ISO-2 code from session data or country name
                                                $code = $session->country_code
                                                    ?? ($session->code ?? null)
                                                    ?? ($session->iso2 ?? null)
                                                    ?? ($session->iso ?? null)
                                                    ?? ($session->alpha2 ?? null);

                                                if (!$code && is_string($session->country ?? null)) {
                                                    $name = strtolower(trim($session->country));
                                                    $map = [
                                                        // Arabic
                                                        'السعودية' => 'sa', 'المملكة العربية السعودية' => 'sa',
                                                        'الأردن' => 'jo', 'الاردن' => 'jo',
                                                        'مصر' => 'eg',
                                                        'فلسطين' => 'ps',
                                                        'الإمارات' => 'ae', 'الامارات' => 'ae', 'الإمارات العربية المتحدة' => 'ae',
                                                        'قطر' => 'qa', 'الكويت' => 'kw', 'البحرين' => 'bh', 'عمان' => 'om', 'اليمن' => 'ye',
                                                        'العراق' => 'iq', 'سوريا' => 'sy', 'لبنان' => 'lb',
                                                        'المغرب' => 'ma', 'الجزائر' => 'dz', 'تونس' => 'tn', 'ليبيا' => 'ly', 'السودان' => 'sd',
                                                        'تركيا' => 'tr',
                                                        'ألمانيا' => 'de', 'المانيا' => 'de', 'فرنسا' => 'fr', 'إيطاليا' => 'it', 'اسبانيا' => 'es', 'إسبانيا' => 'es', 'المملكة المتحدة' => 'gb', 'بريطانيا' => 'gb',
                                                        'الولايات المتحدة' => 'us', 'امريكا' => 'us', 'أمريكا' => 'us', 'كندا' => 'ca', 'أستراليا' => 'au', 'استراليا' => 'au',
                                                        'روسيا' => 'ru', 'الصين' => 'cn', 'الهند' => 'in', 'اليابان' => 'jp', 'كوريا الجنوبية' => 'kr',
                                                        // English
                                                        'saudi arabia' => 'sa', 'kingdom of saudi arabia' => 'sa', 'ksa' => 'sa',
                                                        'jordan' => 'jo', 'hashemite kingdom of jordan' => 'jo',
                                                        'egypt' => 'eg', 'arab republic of egypt' => 'eg',
                                                        'palestine' => 'ps', 'state of palestine' => 'ps',
                                                        'united arab emirates' => 'ae', 'uae' => 'ae',
                                                        'qatar' => 'qa', 'kuwait' => 'kw', 'bahrain' => 'bh', 'oman' => 'om', 'yemen' => 'ye',
                                                        'iraq' => 'iq', 'syria' => 'sy', 'lebanon' => 'lb',
                                                        'morocco' => 'ma', 'algeria' => 'dz', 'tunisia' => 'tn', 'libya' => 'ly', 'sudan' => 'sd',
                                                        'turkey' => 'tr', 'turkiye' => 'tr',
                                                        'germany' => 'de', 'france' => 'fr', 'italy' => 'it', 'spain' => 'es', 'united kingdom' => 'gb', 'uk' => 'uk', 'england' => 'gb',
                                                        'united states' => 'us', 'usa' => 'us', 'canada' => 'ca', 'australia' => 'au',
                                                        'russia' => 'ru', 'china' => 'cn', 'india' => 'in', 'japan' => 'jp', 'south korea' => 'kr',
                                                        'netherlands' => 'nl', 'sweden' => 'se', 'norway' => 'no', 'denmark' => 'dk', 'switzerland' => 'ch', 'austria' => 'at', 'belgium' => 'be', 'poland' => 'pl', 'portugal' => 'pt', 'greece' => 'gr', 'ireland' => 'ie',
                                                        'iran' => 'ir', 'pakistan' => 'pk', 'afghanistan' => 'af',
                                                    ];
                                                    $code = $map[$name] ?? null;
                                                }

                                                $flagPath = $code ? public_path('vendor/blade-flags/country-' . strtolower($code) . '.svg') : null;
                                                $hasFlag = $flagPath && file_exists($flagPath);
                                            @endphp

                                            @if($hasFlag)
                                                <img src="{{ asset('vendor/blade-flags/country-' . strtolower($code) . '.svg') }}" class="me-2" style="width: 20px; height: 15px;" alt="{{ strtoupper($code) }}" />
                                            @else
                                                <i class='tabler-world icon-base ti icon-md me-2 text-muted'></i>
                                            @endif
                                            <span>
                                                {{ $session->city ? $session->city . ', ' : '' }}
                                                {{ $session->country ?: __('Unknown') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Session Details -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">{{ __('Session Information') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('Status') }}</h6>
                                        @if($session->isActive())
                                            <span class="badge bg-label-success">
                                                <i class='tabler-circle-filled me-1'></i> {{ __('Online') }}
                                            </span>
                                        @else
                                            <span class="badge bg-label-secondary">
                                                <i class='tabler-circle-off me-1'></i> {{ __('Offline') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('Last Activity') }}</h6>
                                        <div>{{ $session->last_activity->diffForHumans() }}</div>
                                        <small class="text-muted">{{ $session->last_activity->format('Y-m-d H:i:s') }}</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('Session Start') }}</h6>
                                        <div>{{ $session->created_at->diffForHumans() }}</div>
                                        <small class="text-muted">{{ $session->created_at->format('Y-m-d H:i:s') }}</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-muted">{{ __('Session Duration') }}</h6>
                                        <div>{{ $session->created_at->diffForHumans($session->last_activity, true) }}</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <h6 class="text-muted">{{ __('User Agent') }}</h6>
                                        <div class="bg-light p-2 rounded">
                                            <small class="text-muted">{{ $session->user_agent }}</small>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="text-muted">{{ __('Current Page') }}</h6>
                                        <div class="d-flex align-items-center">
                                            <i class='tabler-link me-2 text-muted'></i>
                                            <a href="{{ $session->url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 100%;">
                                                {{ $session->url }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                @if($recentActivity->isNotEmpty())
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">{{ __('Recent Activity') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Time') }}</th>
                                        <th>{{ __('Page') }}</th>
                                        <th>{{ __('Device') }}</th>
                                        <th>{{ __('Location') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivity as $activity)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span>{{ $activity->last_activity->diffForHumans() }}</span>
                                                <small class="text-muted">{{ $activity->last_activity->format('Y-m-d H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ $activity->url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                {{ $activity->url }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($activity->is_mobile)
                                                <i class='tabler-smartphone me-1 text-primary'></i> {{ __('Mobile') }}
                                            @elseif($activity->is_tablet)
                                                <i class='tabler-tablet me-1 text-info'></i> {{ __('Tablet') }}
                                            @else
                                                <i class='tabler-desktop me-1 text-success'></i> {{ __('Desktop') }}
                                            @endif
                                        </td>
                                        <td>
                                            {{ $activity->city ? $activity->city . ', ' : '' }}
                                            {{ $activity->country ?: __('Unknown') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Ban IP Modal -->
<div class="modal fade" id="banIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Ban IP Address') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.monitoring.bans.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ip" class="form-label">{{ __('IP Address') }}</label>
                        <input type="text" class="form-control" id="ip" name="ip" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">{{ __('Reason') }} <small class="text-muted">{{ __('(Optional)') }}</small></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="{{ __('Reason for banning this IP address') }}"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Ban Duration') }}</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration1" value="1" checked>
                            <label class="form-check-label" for="duration1">{{ __('1 Day') }}</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration7" value="7">
                            <label class="form-check-label" for="duration7">{{ __('1 Week') }}</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration30" value="30">
                            <label class="form-check-label" for="duration30">{{ __('1 Month') }}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="duration" id="permanent" value="permanent">
                            <label class="form-check-label" for="permanent">{{ __('Permanent') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Ban IP') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Ban IP Modal
    document.addEventListener('DOMContentLoaded', function() {
        const banIpModal = document.getElementById('banIpModal');
        if (banIpModal) {
            banIpModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const ip = button.getAttribute('data-ip');
                const modalInput = banIpModal.querySelector('#ip');
                modalInput.value = ip;
            });
        }
    });
</script>
@endpush
