@php
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Route;
  use Illuminate\Support\Str;

  $containerNav = $containerNav ?? 'container-fluid';
  $navbarDetached = $navbarDetached ?? '';
  $randomAvatarNumber = rand(1, 5);
  $defaultAvatar = 'assets/img/avatars/' . $randomAvatarNumber . '.png';
  $defaultLogo = 'assets/img/logo/logo.png';
  $defaultFavicon = 'assets/img/favicon/favicon.ico';
  $profilePhoto = Auth::user() ? Auth::user()->profile_photo_path : null;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
  <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
    <a href="{{ url('/') }}" class="app-brand-link">
      <span class="app-brand-logo edu">
        @php
          $navLogoUrl = asset(config('settings.site_logo', $defaultLogo));
          $navLogoPath = ltrim(parse_url($navLogoUrl, PHP_URL_PATH) ?? '', '/');
          $nav32 = $navLogoPath ? route('img.fit', ['size' => '32x32', 'path' => $navLogoPath]) : $navLogoUrl;
          $nav64 = $navLogoPath ? route('img.fit', ['size' => '64x64', 'path' => $navLogoPath]) : $navLogoUrl;
        @endphp
        <img
          src="{{ $nav32 }}"
          srcset="{{ $nav32 }} 1x, {{ $nav64 }} 2x"
          sizes="20px"
          alt="LogoWebsite"
          style="max-width: 20px; height: auto;">
      </span>
      <span class="app-brand-text edu menu-text fw-bold">{{ config('settings.site_name') }}</span>
    </a>

    <!-- Display menu close icon only for horizontal-menu with navbar-full -->
    @if (isset($menuHorizontal))
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
        <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
      </a>
    @endif
  </div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
  <div
    class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-2 me-xl-4" href="javascript:void(0)">
      <i class="nav-icon ti tabler-menu-2 ti-md"></i>
    </a>
  </div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

  @if (!isset($menuHorizontal))
    <!-- Search -->
    <div class="navbar-nav align-items-center">
      <div class="nav-item navbar-search-wrapper px-md-0 px-2 mb-0">
        <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
          <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
        </a>
      </div>
    </div>
    <!-- /Search -->
  @endif

  <ul class="navbar-nav flex-row align-items-center ms-md-auto">
    @if (isset($menuHorizontal))
      <!-- Search -->
      <li class="nav-item navbar-search-wrapper btn btn-text-secondary btn-icon rounded-pill">
        <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
          <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
        </a>
      </li>
      <!-- /Search -->
    @endif

    <!-- Language -->
    <li class="nav-item dropdown-language dropdown">
      <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
        href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="nav-icon ti tabler-language rounded-circle ti-md"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ url('lang/en') }}"
            data-language="en" data-text-direction="ltr">
            <span>English</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" href="{{ url('lang/ar') }}"
            data-language="ar" data-text-direction="rtl">
            <span>Arabic</span>
          </a>
        </li>
      </ul>
    </li>
    <!--/ Language -->

    @if ($configData['hasCustomizer'] == true)
      <!-- Style Switcher -->
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" id="nav-theme"
          href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="icon-base ti tabler-sun icon-22px theme-icon-active text-heading"></i>
          <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
          <li>
            <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
              aria-pressed="false">
              <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Light</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark"
              aria-pressed="true">
              <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Dark</span>
            </button>
          </li>
          <li>
            <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
              aria-pressed="false">
              <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3"
                  data-icon="device-desktop-analytics"></i>System</span>
            </button>
          </li>
        </ul>
      </li>
      <!-- / Style Switcher-->
    @endif

    <!-- Quick links  -->
    <li class="nav-item dropdown-shortcuts navbar-dropdown dropdown">
      <a class="nav-link btn btn-text-secondary btn-icon rounded-pill btn-icon dropdown-toggle hide-arrow"
        href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <i class="nav-icon ti tabler-layout-grid-add ti-md"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-end p-0">
        <div class="dropdown-menu-header border-bottom">
          <div class="dropdown-header d-flex align-items-center py-3">
            <h6 class="mb-0 me-auto">Shortcuts</h6>
            <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon dropdown-shortcuts-add"
              data-bs-toggle="tooltip" data-bs-placement="top" title="Add shortcuts"><i
                class="nav-icon ti tabler-plus text-heading"></i></a>
          </div>
        </div>
        <div class="dropdown-shortcuts-list scrollable-container">
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="nav-icon ti tabler-device-desktop-analytics ti-26px text-heading"></i>
              </span>
              <a href="{{ route('dashboard.index') }}" class="stretched-link">{{ __('Dashboard') }}</a>
              <small>{{ __('Main Dashboard') }}</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="nav-icon ti tabler-users ti-26px text-heading"></i>
              </span>
              <a href="{{ route('dashboard.users.index') }}" class="stretched-link">{{ __('Users') }}</a>
              <small>{{ __('Manage Users') }}</small>
            </div>
          </div>
          <div class="row row-bordered overflow-visible g-0">
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="nav-icon ti tabler-settings ti-26px text-heading"></i>
              </span>
              <a href="{{ route('dashboard.settings.index') }}" class="stretched-link">{{ __('Settings') }}</a>
              <small>{{ __('System Settings') }}</small>
            </div>
            <div class="dropdown-shortcuts-item col">
              <span class="dropdown-shortcuts-icon rounded-circle mb-3">
                <i class="nav-icon ti tabler-message ti-26px text-heading"></i>
              </span>
              <a href="{{ route('dashboard.messages.index') }}" class="stretched-link">{{ __('Messages') }}</a>
              <small>{{ __('Inbox & Communications') }}</small>
            </div>
          </div>
        </div>
      </div>
    </li>
    <!-- Quick links -->

    @if (auth()->check())
      <!-- Notification -->
      <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
        <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
          href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
          <span class="position-relative">
            <i class="nav-icon ti tabler-bell ti-md"></i>
            @if (auth()->user()->unreadNotifications->count() > 0)
              <span
                class="position-absolute top-0 start-100 translate-middle-x badge rounded-pill bg-danger notification-badge"
                style="font-size: 0.65rem; transform: translate(-50%, -50%);">
                {{ auth()->user()->unreadNotifications->count() }}
                <span class="visually-hidden">{{ __('unread notifications') }}</span>
              </span>
            @endif
          </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end py-0">
          <li class="dropdown-menu-header border-bottom">
            <div class="dropdown-header d-flex align-items-center py-3">
              <h6 class="mb-0 me-auto">{{ __('Notifications') }}</h6>
              @if (auth()->user()->unreadNotifications->count() > 0)
                <a href="{{ route('dashboard.notifications.mark-all-as-read') }}"
                  class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="{{ __('Mark all as read') }}">
                  <i class="nav-icon ti tabler-mail-opened"></i>
                </a>
              @endif
            </div>
          </li>
          <li class="dropdown-notifications-list scrollable-container">
            <ul class="list-group list-group-flush">
              @forelse(auth()->user()->unreadNotifications->take(5) as $notification)
                <li class="list-group-item list-group-item-action dropdown-notifications-item">
                  <div class="d-flex gap-2">
                    <div class="flex-shrink-0">
                      <div class="avatar">
                        <span
                          class="avatar-initial rounded-circle {{ $notification->data['icon_class'] ?? 'bg-primary' }}">
                          <i class="{{ $notification->data['icon'] ?? 'ti tabler-bell' }}"></i>
                        </span>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1">{{ $notification->data['title'] ?? __('Notification') }}</h6>
                      <p class="mb-0">{{ $notification->data['message'] ?? '' }}</p>
                      <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="flex-shrink-0 dropdown-notifications-actions">
                      <a href="{{ route('dashboard.notifications.mark-as-read', $notification->id) }}"
                        class="dropdown-notifications-read" data-bs-toggle="tooltip" data-bs-placement="top"
                        title="{{ __('Mark as read') }}">
                        <span
                          class="d-flex align-items-center justify-content-center size-35 rounded-circle bg-label-secondary">
                          <i class="nav-icon ti tabler-mail fs-4"></i>
                        </span>
                      </a>
                    </div>
                  </div>
                </li>
              @empty
                <li class="list-group-item list-group-item-action dropdown-notifications-item text-center py-4">
                  <div class="text-muted">{{ __('No new notifications') }}</div>
                </li>
              @endforelse
            </ul>
          </li>
          <li class="dropdown-menu-footer border-top p-2">
            <a href="{{ route('dashboard.notifications.index') }}"
              class="btn btn-primary d-flex justify-content-center">
              {{ __('View all notifications') }}
            </a>
          </li>
        </ul>
      </li>
      <!--/ Notification -->
    @endif
    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown px-2">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          <img
            src="
                    @if ($profilePhoto && Str::startsWith($profilePhoto, ['http://', 'https://'])) {{ $profilePhoto }}
                    @elseif($profilePhoto)
                        {{ asset('storage/' . $profilePhoto) }}
                    @else
                        {{ asset($defaultAvatar) }} @endif
                "
            alt="Avatar" class="rounded-circle">
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item mt-0"
            href="{{ Route::has('dashboard.users.show') ? route('dashboard.users.show', Auth::user()->id) : url('pages/profile-user') }}">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  <img
                    src="
                        @if ($profilePhoto && Str::startsWith($profilePhoto, ['http://', 'https://'])) {{ $profilePhoto }}
                        @elseif($profilePhoto)
                            {{ asset('storage/' . $profilePhoto) }}
                        @else
                            {{ asset($defaultAvatar) }} @endif
                    "
                    alt="Avatar" class="rounded-circle">
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">
                  @if (Auth::check())
                    {{ Auth::user()->name }}
                  @else
                    John Doe
                  @endif
                </h6>
                <small class="text-muted">
                  @if (Auth::check())
                    {{ Auth::user()->roles->pluck('name')->first() }}
                  @else
                    Admin
                  @endif
                </small>
              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('dashboard.users.show') ? route('dashboard.users.show', Auth::user()->id) : url('pages/profile-user') }}">
            <i class="nav-icon ti tabler-user me-3 ti-md"></i><span class="align-middle">My Profile</span>
          </a>
        </li>

        @if (Auth::check() && Laravel\Jetstream\Jetstream::hasTeamFeatures())
          <li>
            <div class="dropdown-divider my-1 mx-n2"></div>
          </li>
          <li>
            <h6 class="dropdown-header">Manage Team</h6>
          </li>
          <li>
            <div class="dropdown-divider my-1 mx-n2"></div>
          </li>
          <li>
            <a class="dropdown-item"
              href="{{ Auth::user() ? route('teams.show', Auth::user()->currentTeam->id) : 'javascript:void(0)' }}">
              <i class="nav-icon ti tabler-settings ti-md me-3"></i><span class="align-middle">Team Settings</span>
            </a>
          </li>
          @can('create', Laravel\Jetstream\Jetstream::newTeamModel())
            <li>
              <a class="dropdown-item" href="{{ route('teams.create') }}">
                <i class="nav-icon ti tabler-user ti-md me-3"></i><span class="align-middle">Create New Team</span>
              </a>
            </li>
          @endcan

          @if (Auth::user()->allTeams()->count() > 1)
            <li>
              <div class="dropdown-divider my-1 mx-n2"></div>
            </li>
            <li>
              <h6 class="dropdown-header">Switch Teams</h6>
            </li>
            <li>
              <div class="dropdown-divider my-1 mx-n2"></div>
            </li>
          @endif

          @if (Auth::user())
            @foreach (Auth::user()->allTeams() as $team)
              {{-- <x-switchable-team :team="$team" /> --}}
            @endforeach
          @endif
        @endif
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        @if (Auth::check())
          <li>
            <div class="d-grid px-2 pt-2 pb-1">
              <form method="POST" action="{{ route('logout') }}" class="mb-0 w-100">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger d-flex w-100">
                  <small class="align-middle">Logout</small>
                  <i class="nav-icon ti tabler-logout ms-2 ti-14px"></i>
                </button>
              </form>
            </div>
          </li>
        @else
          <li>
            <div class="d-grid px-2 pt-2 pb-1">
              <a class="btn btn-sm btn-danger d-flex"
                href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                <small class="align-middle">Login</small>
                <i class="nav-icon ti tabler-login ms-2 ti-14px"></i>
              </a>
            </div>
          </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>

@vite([
  'resources/assets/js/notifications/bell.js',
])
