@extends('content.dashboard.monitoring.layout')

@section('title', __('Active Visitors'))



@push('styles')
  <style>
    .visitor-flag {
      width: 24px;
      height: 16px;
      border: 1px solid #e9ecef;
      border-radius: 2px;
      object-fit: cover;
    }

    .browser-icon {
      width: 20px;
      height: 20px;
      margin-right: 8px;
    }

    .device-icon {
      font-size: 1.1rem;
      margin-right: 8px;
    }

    .visitor-card {
      transition: all 0.3s ease;
      cursor: pointer;
      border: 1px solid #f5f5f9;
      border-radius: 0.5rem;
    }

    .visitor-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
      border-color: #e9ecef;
    }

    .online-badge {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 6px;
    }

    .online {
      background-color: #71dd37;
    }

    .stats-card {
      transition: transform 0.2s;
    }

    .stats-card:hover {
      transform: translateY(-3px);
    }
  </style>
@endpush

@section('monitoring-content')
  <div class="row">
    <!-- Stats Cards -->
    <div class="col-12 mb-4">
      <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card stats-card bg-label-primary">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Total Visitors') }}</h6>
                  <h3 class="mb-0">{{ $stats['total'] }}</h3>
                </div>
                <div class="avatar bg-label-primary p-2 rounded">
                  <i class="tabler-user icon-base ti icon-md"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card stats-card bg-label-success">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Registered Members') }}</h6>
                  <h3 class="mb-0">{{ $stats['members'] }}</h3>
                </div>
                <div class="avatar bg-label-success p-2 rounded">
                  <i class="tabler-user-check icon-base ti icon-md"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card stats-card bg-label-warning">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Guests') }}</h6>
                  <h3 class="mb-0">{{ $stats['guests'] }}</h3>
                </div>
                <div class="avatar bg-label-warning p-2 rounded">
                  <i class="tabler-user-exclamation icon-base ti icon-md"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card stats-card bg-label-danger">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Bots') }}</h6>
                  <h3 class="mb-0">{{ $stats['bots'] }}</h3>
                </div>
                <div class="avatar bg-label-danger p-2 rounded">
                  <i class="tabler-robot icon-base ti icon-md"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Members by Visits and Top Countries -->
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('Top Members by Visits') }}</h5>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text"><i class="tabler-search"></i></span>
                <input type="text" id="topMembersSearch" class="form-control" placeholder="{{ __('Search name or email') }}" value="{{ $topMembersQuery ?? '' }}">
              </div>
              <select id="topMembersSort" class="form-select form-select-sm" style="width: 160px;">
                <option value="visits" @selected(($topMembersSort ?? 'visits') === 'visits')>{{ __('Sort by visits') }}</option>
                <option value="recent" @selected(($topMembersSort ?? 'visits') === 'recent')>{{ __('Sort by recent') }}</option>
              </select>
              <select id="topMembersPerPage" class="form-select form-select-sm" style="width: 120px;">
                @foreach([10,20,30,50] as $n)
                  <option value="{{ $n }}" @selected(($topMembersPerPage ?? 20) == $n)>{{ $n }} / {{ __('page') }}</option>
                @endforeach
              </select>
              <div class="dropdown">
                <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="tabler-dots-vertical icon-base ti icon-md"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="{{ route('dashboard.monitoring.visitors.index', array_merge(request()->query(), ['timeframe' => 'today'])) }}">{{ __('Today') }}</a></li>
                  <li><a class="dropdown-item" href="{{ route('dashboard.monitoring.visitors.index', array_merge(request()->query(), ['timeframe' => 'yesterday'])) }}">{{ __('Yesterday') }}</a></li>
                  <li><a class="dropdown-item" href="{{ route('dashboard.monitoring.visitors.index', array_merge(request()->query(), ['timeframe' => 'week'])) }}">{{ __('This Week') }}</a></li>
                  <li><a class="dropdown-item" href="{{ route('dashboard.monitoring.visitors.index', array_merge(request()->query(), ['timeframe' => 'month'])) }}">{{ __('This Month') }}</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          @php
            $collectionForMax = ($topMembers instanceof \Illuminate\Contracts\Pagination\Paginator || $topMembers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
              ? collect($topMembers->items())
              : (collect($topMembers ?? []));
            $maxVisits = $collectionForMax->count() ? max(1, (int) $collectionForMax->max('visits')) : 1;
          @endphp
          <div class="table-responsive">
            <table id="topMembersTable" class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ __('Member') }}</th>
                  <th>{{ __('Location') }}</th>
                  <th>{{ __('Platform') }}</th>
                  <th class="text-center">{{ __('Visits') }}</th>
                  <th class="w-25">{{ __('Engagement') }}</th>
                  <th>{{ __('Last Activity') }}</th>
                  <th>{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody>
                @php $rankOffset = method_exists($topMembers, 'firstItem') ? ($topMembers->firstItem() - 1) : 0; @endphp
                @forelse($topMembers as $idx => $row)
                  <tr>
                    <td class="fw-medium">
                      @php $rank = $rankOffset + $idx + 1; @endphp
                      @if($rank === 1)
                        <span class="me-1">🥇</span>
                      @elseif($rank === 2)
                        <span class="me-1">🥈</span>
                      @elseif($rank === 3)
                        <span class="me-1">🥉</span>
                      @endif
                      {{ $rank }}
                    </td>
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2" style="width: 36px; height: 36px;">
                          @php
                            $user = $row->user;
                            $profilePath = $user?->profile_photo_path ?? null;
                            $fallbackUrl = $user?->profile_photo_url ?? null;
                            $isExternal = is_string($profilePath) && (str_starts_with($profilePath, 'http://') || str_starts_with($profilePath, 'https://'));
                            if ($isExternal) {
                                $photoUrl = $profilePath; $cacheBust = '';
                            } elseif ($profilePath) {
                                $photoUrl = asset('storage/' . ltrim($profilePath, '/')); $cacheBust = '?' . time();
                            } else {
                                $photoUrl = $fallbackUrl; $cacheBust = '';
                            }
                          @endphp
                          @if ($photoUrl)
                            <img src="{{ $photoUrl }}{{ $cacheBust }}" alt="{{ $user?->name }}" class="rounded-circle h-100 w-100 object-fit-cover" @if($fallbackUrl) onerror="this.onerror=null; this.src='{{ $fallbackUrl }}';" @endif>
                          @else
                            <div class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center h-100">
                              <i class="tabler-user icon-base ti text-muted"></i>
                            </div>
                          @endif
                        </div>
                        <div>
                          <div class="fw-medium">{{ $user?->name ?? __('Unknown') }}</div>
                          <div class="small text-muted">{{ $user?->email }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      @php
                        $latest = $topMembersLatestSessions[$row->user_id] ?? null;
                        $cc = $latest?->country_code;
                        $city = $latest?->city;
                        $country = $latest?->country;
                      @endphp
                      <div class="d-flex align-items-center">
                        @if ($cc)
                          <div class="me-2">
                            <x-flag-country-{{ strtolower($cc) }} class="visitor-flag" />
                          </div>
                        @endif
                        <div>
                          <div class="fw-medium">{{ $country ?: __('Unknown') }}</div>
                          <div class="small text-muted">{{ $city ?: __('Unknown') }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      @php
                        $platform = $latest?->platform;
                        $browser = $latest?->browser;
                      @endphp
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if ($platform)
                          <span class="badge bg-label-info">{{ $platform }}</span>
                        @endif
                        @if ($browser)
                          <span class="badge bg-label-secondary">{{ $browser }}</span>
                        @endif
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-label-primary rounded-pill">{{ number_format((int) $row->visits) }}</span>
                    </td>
                    <td>
                      @php
                        $percent = min(100, (int) round(($row->visits / $maxVisits) * 100));
                      @endphp
                      <div class="d-flex align-items-center">
                        <div class="progress w-100 me-2" style="height: 8px;">
                          <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">{{ $percent }}%</small>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="fw-medium">{{ optional($row->last_activity)->diffForHumans() }}</span>
                        <small class="text-muted">{{ optional($row->last_activity)->format('Y-m-d H:i') }}</small>
                      </div>
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="tabler-dots-vertical icon-base ti icon-md"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          @if($row->user_id)
                          <li>
                            <a class="dropdown-item" href="{{ route('dashboard.users.show', $row->user_id) }}">
                              <i class="tabler-user me-2 icon-base ti icon-md"></i>{{ __('View User Profile') }}
                            </a>
                          </li>
                          @endif
                          <li>
                            <a class="dropdown-item" href="#" onclick="navigator.clipboard.writeText('{{ $row->user?->email }}');">
                              <i class="tabler-copy me-2 icon-base ti icon-md"></i>{{ __('Copy Email') }}
                            </a>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-3">{{ __('No data available') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
            @if(method_exists($topMembers, 'hasPages') && $topMembers->hasPages())
              <div class="mt-3">
                {{ $topMembers->withQueryString()->links() }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Top Countries') }}</h5>
          <span class="badge bg-label-primary rounded-pill">{{ count($locations) }} {{ __('Countries') }}</span>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-borderless">
              <tbody>
                @forelse($locations->take(8) as $country => $data)
                  <tr>
                    <td class="w-100">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                          @php
                            // 1) Try common keys from the data payload
                            $code = $data['country_code']
                              ?? $data['code']
                              ?? $data['iso2']
                              ?? $data['iso']
                              ?? $data['alpha2']
                              ?? null;

                            // 2) If $country itself looks like ISO-2 (e.g., 'DE'), use it
                            if (!$code && is_string($country)) {
                              $trimmed = trim($country);
                              if (preg_match('/^[A-Za-z]{2}$/', $trimmed)) {
                                $code = strtolower($trimmed);
                              }
                            }

                            // 3) Map Arabic/English names to ISO-2
                            if (!$code) {
                              $map = [
                                // Arabic common
                                'السعودية' => 'sa', 'المملكة العربية السعودية' => 'sa',
                                'الأردن' => 'jo', 'الاردن' => 'jo',
                                'مصر' => 'eg',
                                'فلسطين' => 'ps',
                                'الإمارات' => 'ae', 'الامارات' => 'ae', 'الإمارات العربية المتحدة' => 'ae',
                                'قطر' => 'qa', 'الكويت' => 'kw', 'البحرين' => 'bh', 'عمان' => 'om', 'اليمن' => 'ye',
                                'العراق' => 'iq', 'سوريا' => 'sy', 'لبنان' => 'lb',
                                'المغرب' => 'ma', 'الجزائر' => 'dz', 'تونس' => 'tn', 'ليبيا' => 'ly', 'السودان' => 'sd',
                                'تركيا' => 'tr',
                                'ألمانيا' => 'de', 'المانيا' => 'de', 'فرنسا' => 'fr', 'إيطاليا' => 'it', 'اسبانيا' => 'es', 'إسبانيا' => 'es', 'المملكة المتحدة' => 'gb', 'بريطانيا' => 'gb', 'انجلترا' => 'gb', 'إنجلترا' => 'gb',
                                'الولايات المتحدة' => 'us', 'امريكا' => 'us', 'أمريكا' => 'us', 'كندا' => 'ca', 'أستراليا' => 'au', 'استراليا' => 'au',
                                'روسيا' => 'ru', 'الصين' => 'cn', 'الهند' => 'in', 'اليابان' => 'jp', 'كوريا الجنوبية' => 'kr',
                                // English common
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
                              $key = is_string($country) ? strtolower(trim($country)) : '';
                              $code = $map[$key] ?? null;
                            }

                            // 4) Validate that the asset exists; some packages include both gb and uk, we already have both in /public
                            $flagPath = $code ? public_path('vendor/blade-flags/country-' . strtolower($code) . '.svg') : null;
                            $hasFlag = $flagPath && file_exists($flagPath);
                          @endphp
                          @if ($hasFlag)
                            <img src="{{ asset('vendor/blade-flags/country-' . strtolower($code) . '.svg') }}" class="visitor-flag me-2" alt="{{ strtoupper($code) }}">
                          @else
                            <i class='tabler-world icon-base ti icon-md me-2 text-muted'></i>
                          @endif
                          <span>{{ $country ?: __('Unknown') }}</span>
                        </div>
                        <span class="badge bg-label-primary rounded-pill">{{ $data['count'] }}</span>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td class="text-center text-muted py-3">{{ __('No data available') }}</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Visitors Table -->
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Active Visitor Sessions') }}</h5>
          <div class="d-flex">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class='tabler-search icon-base ti icon-md me-2'></i></span>
              <input type="text" class="form-control" id="searchInput" placeholder="{{ __('Search...') }}">
            </div>
            <button class="btn btn-icon btn-outline-primary ms-2" id="refreshBtn">
              <i class='tabler-refresh icon-base ti icon-md me-2'></i>
            </button>
          </div>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('User') }}</th>
                <th>{{ __('Country') }}</th>
                <th>{{ __('Browser') }}</th>
                <th>{{ __('Device') }}</th>
                <th>{{ __('Last Activity') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse($visitors as $visitor)
                <tr class="visitor-card" data-visitor-id="{{ $visitor->id }}">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-2" style="width: 36px; height: 36px;">
                        @if ($visitor->user)
                          @php
                            $profilePath = $visitor->user->profile_photo_path;
                            $fallbackUrl = $visitor->user->profile_photo_url ?? null;
                            $isExternal = is_string($profilePath) && (str_starts_with($profilePath, 'http://') || str_starts_with($profilePath, 'https://'));
                            if ($isExternal) {
                                $photoUrl = $profilePath;
                                $cacheBust = '';
                            } elseif ($profilePath) {
                                $photoUrl = asset('storage/' . ltrim($profilePath, '/'));
                                $cacheBust = '?' . time();
                            } else {
                                $photoUrl = $fallbackUrl; // may be external or default avatar
                                $cacheBust = '';
                            }
                          @endphp
                          @if ($photoUrl)
                            <img src="{{ $photoUrl }}{{ $cacheBust }}"
                              alt="{{ $visitor->user->name }}" class="rounded-circle h-100 w-100 object-fit-cover"
                              @if($fallbackUrl) onerror="this.onerror=null; this.src='{{ $fallbackUrl }}';" @endif>
                          @else
                            <div
                              class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center h-100">
                              <i class="tabler-user icon-base ti text-muted"></i>
                            </div>
                          @endif
                        @else
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class='tabler-user icon-base ti icon-md me-2'></i>
                          </span>
                        @endif
                      </div>
                      <div>
                        <span class="fw-medium">{{ $visitor->user ? $visitor->user->name : __('Guest') }}</span>
                        <div class="text-muted small">
                          <span class="online-badge online"></span>
                          <small>{{ $visitor->is_bot ? __('Bot') : __('Active Now') }}</small>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      @if ($visitor->country_code)
                        <div class="me-2">
                          <x-flag-country-{{ strtolower($visitor->country_code) }} class="visitor-flag" />
                        </div>
                      @endif
                      <div>
                        <div class="fw-medium">{{ $visitor->country ?: __('Unknown') }}</div>
                        <div class="small text-muted">{{ $visitor->city ?: __('Unknown') }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      @php
                        $browserIcon = match (strtolower($visitor->browser_family)) {
                            'chrome' => 'ti tabler-brand-chrome',
                            'firefox' => 'ti tabler-brand-firefox',
                            'safari' => 'ti tabler-brand-safari',
                            'edge' => 'ti tabler-brand-edge',
                            'opera' => 'ti tabler-brand-opera',
                            'ie' => 'ti tabler-brand-internet-explorer',
                            'yandex' => 'ti tabler-brand-yandex',
                            'brave' => 'ti tabler-brand-brave',
                            'vivaldi' => 'ti tabler-brand-vivaldi',
                            // Fallbacks for lesser-known browsers
                            'samsung internet' => 'ti tabler-browser',
                            'uc browser' => 'ti tabler-browser',
                            'silk' => 'ti tabler-browser',
                            'maxthon' => 'ti tabler-browser',
                            'sogou explorer' => 'ti tabler-browser',
                            default => 'ti tabler-browser',
                        };
                      @endphp
                      <div class="avatar avatar-sm me-2">
                        <div class="avatar-initial rounded bg-label-primary">
                          <i class="ti {{ $browserIcon }} icon-base icon-md me-2 text-primary"></i>
                        </div>
                      </div>
                      <div>
                        <div class="fw-medium">{{ $visitor->browser }}</div>
                        <div class="small text-muted">{{ $visitor->browser_version }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center">
                      @if ($visitor->is_mobile)
                        <i class='tabler-smartphone device-icon text-primary'></i>
                        <span>{{ __('Mobile') }}</span>
                      @elseif($visitor->is_tablet)
                        <i class='tabler-tablet device-icon text-info'></i>
                        <span>{{ __('Tablet') }}</span>
                      @else
                        <i class='tabler-desktop device-icon text-success'></i>
                        <span>{{ __('Desktop') }}</span>
                      @endif
                    </div>
                    <div class="small text-muted">{{ $visitor->platform }}</div>
                  </td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-medium">{{ $visitor->last_activity->diffForHumans() }}</span>
                      <small class="text-muted">{{ $visitor->last_activity->format('Y-m-d H:i') }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="dropdown">
                      <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="tabler-dots-vertical icon-base ti icon-md"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                          <a class="dropdown-item" href="{{ route('dashboard.monitoring.visitors.show', $visitor->id) }}">
                            <i class="tabler-eye me-2 icon-base ti icon-md"></i>{{ __('View Details') }}
                          </a>
                        </li>
                        @can('ban ip')
                        <li>
                          <a class="dropdown-item text-danger ban-ip" href="#" data-ip="{{ $visitor->ip }}" data-bs-toggle="modal" data-bs-target="#banIpModal">
                            <i class="tabler-ban me-2 icon-base ti icon-md"></i>{{ __('Ban IP') }}
                          </a>
                        </li>
                        @endcan
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <a class="dropdown-item" href="#" onclick="navigator.clipboard.writeText('{{ $visitor->ip }}');">
                            <i class="tabler-copy me-2 icon-base ti icon-md"></i>{{ __('Copy IP') }}
                          </a>
                        </li>
                        @if($visitor->user_id)
                        <li>
                          <a class="dropdown-item" href="{{ route('dashboard.users.show', $visitor->user_id) }}">
                            <i class="tabler-user me-2 icon-base ti icon-md"></i>{{ __('View User Profile') }}
                          </a>
                        </li>
                        @endif
                      </ul>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                      <i class='tabler-user-x tabler-lg mb-2 text-muted'></i>
                      <span class="text-muted">{{ __('No active sessions found') }}</span>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($visitors->hasPages())
          <div class="card-footer">
            {{ $visitors->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Ban IP Modal -->
  <div class="modal fade" id="banIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ __('Ban IP Address') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="banIpForm" action="{{ route('dashboard.monitoring.bans.store') }}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
              <label for="ip" class="form-label">{{ __('IP Address') }}</label>
              <input type="text" class="form-control" id="ip" name="ip" readonly>
            </div>
            <div class="mb-3">
              <label for="reason" class="form-label">{{ __('Reason (optional)') }}</label>
              <textarea class="form-control" id="reason" name="reason" rows="3"
                placeholder="{{ __('Reason for banning this IP address') }}"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">{{ __('Ban Duration') }}</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="duration" id="duration1" value="1" checked>
                <label class="form-check-label" for="duration1">{{ __('1 day') }}</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="duration" id="duration7" value="7">
                <label class="form-check-label" for="duration7">{{ __('1 week') }}</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="duration" id="duration30" value="30">
                <label class="form-check-label" for="duration30">{{ __('1 month') }}</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="duration" id="permanent" value="permanent">
                <label class="form-check-label" for="permanent">{{ __('Permanent') }}</label>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary"
              data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-danger">{{ __('Confirm Ban') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ---- Top Members controls ----
      const qInput = document.getElementById('topMembersSearch');
      const sortSelect = document.getElementById('topMembersSort');
      const perPageSelect = document.getElementById('topMembersPerPage');

      function updateTopMembersParams(newParams = {}) {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        // Preserve existing params, update provided ones
        Object.entries(newParams).forEach(([k, v]) => {
          if (v === undefined || v === null || v === '') {
            params.delete(k);
          } else {
            params.set(k, v);
          }
        });
        // Always reset to first page on filter/sort change
        params.delete('page');
        url.search = params.toString();
        window.location.assign(url.toString());
      }

      let searchDebounce;
      if (qInput) {
        qInput.addEventListener('keyup', (e) => {
          if (e.key === 'Enter') {
            updateTopMembersParams({ q: qInput.value });
            return;
          }
          clearTimeout(searchDebounce);
          searchDebounce = setTimeout(() => {
            updateTopMembersParams({ q: qInput.value });
          }, 600);
        });
        qInput.addEventListener('change', () => updateTopMembersParams({ q: qInput.value }));
      }
      if (sortSelect) {
        sortSelect.addEventListener('change', () => updateTopMembersParams({ sort: sortSelect.value }));
      }
      if (perPageSelect) {
        perPageSelect.addEventListener('change', () => updateTopMembersParams({ per_page: perPageSelect.value }));
      }

      // Ban IP functionality
      const banIpModal = document.getElementById('banIpModal');
      if (banIpModal) {
        banIpModal.addEventListener('show.bs.modal', function(event) {
          const button = event.relatedTarget;
          const ip = button.getAttribute('data-ip');
          const modalInput = banIpModal.querySelector('#ip');
          modalInput.value = ip;
        });
      }

      // Search functionality
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('keyup', function() {
          const searchText = this.value.toLowerCase();
          const rows = document.querySelectorAll('tbody tr');

          rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
          });
        });
      }

      // Refresh button
      const refreshBtn = document.getElementById('refreshBtn');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
          window.location.reload();
        });
      }

      // Auto-refresh every 30 seconds
      setInterval(() => {
        window.location.reload();
      }, 30000);
    });
  </script>
@endpush
