@php
$configData = Helper::appClasses();
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
$colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
$colorCount = count($colors);

$icons = [
'1' => 'grade-icon ti tabler-number-0',
'2' => 'grade-icon ti tabler-number-1',
'3' => 'grade-icon ti tabler-number-2',
'4' => 'grade-icon ti tabler-number-3',
'5' => 'grade-icon ti tabler-number-4',
'6' => 'grade-icon ti tabler-number-5',
'7' => 'grade-icon ti tabler-number-6',
'8' => 'grade-icon ti tabler-number-7',
'9' => 'grade-icon ti tabler-number-8',
'10' => 'grade-icon ti tabler-number-9',
'11' => 'grade-icon ti tabler-number-10-small',
'12' => 'grade-icon ti tabler-number-11-small',
'13' => 'grade-icon ti tabler-number-12-small',
'default' => 'grade-icon ti tabler-book',
];
@endphp

@extends('layouts/layoutFront')

@section('title')

@section('page-style')
@vite([
    'resources/assets/vendor/scss/but.scss',
    'resources/assets/vendor/scss/calendar.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/css/banner-professional.css',
     
])
<!-- Professional Banner CSS -->

<style>
  /* Normalize buttons height to prevent text/font swap reflows */
  .bubbly-button { min-height: 56px; }
  .bubbly-button .grade-icon { line-height: 1; }

  /* Ensure category list items have stable layout */
  a.text-reset.border.rounded-3.p-2.bg-white.shadow-sm { min-height: 64px; }

  /* Images already have intrinsic sizes; keep object-fit without reflow */
  img.object-fit-cover { display: block; }

  /* Reserve calendar space to avoid reflow on JS init */
  .calendar-wrapper { min-height: 420px; }
  .calendar { min-height: 420px; }
  .calendar .days { min-height: 320px; }
  .calendar .day { min-height: 48px; }

  /* Reserve space for the classes grid to avoid row height jump */
  .row.classes-grid { min-height: 420px; }
  @media (min-width: 768px) {
    .row.classes-grid { min-height: 520px; }
  }
  @media (min-width: 1200px) {
    .row.classes-grid { min-height: 640px; }
  }
</style>
@endsection

@section('page-script')
@vite([
    'resources/assets/vendor/js/filterhome.js',
    'resources/assets/vendor/js/but.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/js/appCalender.js',
    'resources/js/banner-professional.js',
])
<!-- Professional Banner JavaScript -->
 
@endsection

@section('content')
<section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
  <!-- Background Pattern -->
  <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

  <!-- Animated Shapes -->
  <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
  <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

  <div class="container position-relative">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8 text-center">

        <!-- Main Title (no animation to improve LCP) -->
        <h1 class="display-4 text-white mb-4" style="font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
          {{ __('welcome') }}
          <span class="text-primary" style="color: #3498db !important;">{{ config('settings.site_name') }}</span>
        </h1>
        @guest
        <!-- Call to Action Buttons -->
        <div class="d-flex justify-content-center gap-3 animate__animated animate__fadeInUp animate__delay-1s">
          <a href="{{ route('login') }}" class="btn btn-primary btn-lg" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);">
            <i class="home-icon ti tabler-user-plus me-2"></i>{{ __('Get Started') }}
          </a>
          <a href="#features" class="btn btn-outline-light btn-lg">
            <i class="home-icon ti tabler-info-circle me-2"></i>{{ __('Learn More') }}
          </a>
        </div>
        @endguest
      </div>
    </div>
  </div>

  <!-- Wave Shape Divider -->
  <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
      <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #ffffff;"></path>
    </svg>
  </div>
</section>

<div class="container px-4 mt-6">
  <ol class="breadcrumb breadcrumb-style2" aria-label="breadcrumbs">
    <li class="breadcrumb-item">
      <a href="{{ route('home') }}">
        <i class="home-icon ti tabler-home-check"></i>
        {{ __('Home') }}
      </a>
    </li>
  </ol>
  <div class="progress mt-2">
    <div
      class="progress-bar"
      role="progressbar"
      style="width: 25%;"
      aria-label="{{ __('Page progress') }}"
      aria-valuenow="25"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <span class="visually-hidden">25%</span>
    </div>
  </div>
</div>

<section class="section-py pt-3 " id="testimonials">

  <div class="school-classes container py-5">
    <div class="card">
      <div class="card-body">

        <div class="row">
          <div class="col-md-7 mb-4">
            <div class="row classes-grid">
              @forelse($classes as $index => $class)
              @php
              $icon = $icons[$class->grade_level] ?? $icons['default'];
              $routeName = request()->is('dashboard/*') ? 'dashboard.class.show' : 'frontend.lesson.show';
              $color = $colors[$index % $colorCount];
              $database = session('database', 'jo');
              @endphp
              <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 mb-4">
                <a href="{{ route($routeName,  ['database' => $database, 'id' => $class->id]) }}"
                  class="btn btn-outline-{{ $color }} bubbly-button btn-block d-flex align-items-center justify-content-center"
                  style="padding: 15px;">
                  {{ $class->grade_name }}
                </a>
              </div>
              @empty
              <div class="col-12">
                <p class="text-center">{{ __('No classes available.') }}</p>
              </div>
              @endforelse
            </div>

            <!-- Professional Banner -->
            <div class="mt-4">
                @include('components.banner-professional')
            </div>

          </div>
          <div class="col-md-5 mb-4">


            <div class="calendar-wrapper">
              <div class="calendar">
                <div class="month-year">
                  <button class="nav-btn" type="button" aria-label="{{ __('Previous month') }}" aria-controls="calendarDays" onclick="prevMonth()">
                    <i class="calendar-icon ti tabler-chevron-right" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ __('Previous month') }}</span>
                  </button>
                  <span id="currentMonthYear" role="status" aria-live="polite"></span>
                  <button class="nav-btn" type="button" aria-label="{{ __('Next month') }}" aria-controls="calendarDays" onclick="nextMonth()">
                    <i class="calendar-icon ti tabler-chevron-left" aria-hidden="true"></i>
                    <span class="visually-hidden">{{ __('Next month') }}</span>
                  </button>
                </div>
                <div class="days" id="calendarDays">
                  @foreach(['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] as $day)
                  <div class="day-label">{{ $day }}</div>
                  @endforeach

                  @foreach($calendar as $date => $events)
                  @php
                  $dateObj = \Carbon\Carbon::parse($date);
                  $isToday = $dateObj->isToday();
                  $hasEvents = count($events) > 0;
                  $isDull = $dateObj->month != $currentMonth;
                  @endphp

                  <div class="day {{ $isToday ? 'today' : '' }}
                                              {{ $hasEvents ? 'event' : '' }}
                                              {{ $isDull ? 'dull' : '' }}"
                    @if($hasEvents)
                    data-bs-toggle="modal"
                    data-bs-target="#eventModal"
                    data-title="{{ $events[0]['title'] }}"
                    data-description="{{ $events[0]['description'] }}"
                    data-date="{{ $date }}"
                    @endif>
                    <div class="content">{{ $dateObj->day }}</div>
                  </div>
                  @endforeach
                </div>
              </div>


            </div>

            <div class="row mb-4 mt-12" style="padding-right: 50px;padding-left: 50px;">
              <h2 class="text-center mb-4">{{ __('Quick search') }}</h2>
              <form id="filter-form" method="GET" action="{{ route('files.filter') }}">
                @csrf
                <div class="row mb-4">
                  <div class="form-group">
                    <label for="class-select">{{ __('Select Class') }}</label>
                    <select id="class-select" name="class_id" class="form-control">
                      <option value="">{{ __('Select Class') }}</option>
                      @foreach($classes as $class)
                      <option value="{{ $class->id }}">{{ $class->grade_name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="subject-select">{{ __('Select Subject') }}</label>
                    <select id="subject-select" name="subject_id" class="form-control" disabled>
                      <option value="">{{ __('Select Subject') }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="semester-select">{{ __('Select Semester') }}</label>
                    <select id="semester-select" name="semester_id" class="form-control" disabled>
                      <option value="">{{ __('Select Semester') }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="file_category">{{ __('File Category') }}</label>
                    <select class="form-control" id="file_category" name="file_category">
                      <option value="">{{ __('Select Category') }}</option>
                      <option value="plans">{{ __('Plans') }}</option>
                      <option value="papers">{{ __('Papers') }}</option>
                      <option value="tests">{{ __('Tests') }}</option>
                      <option value="books">{{ __('Books') }}</option>
                    </select>
                  </div>
                </div>
                <div class="text-center mt-4">
                  <button type="submit" class="btn btn-primary w-100 mb-2" style="max-width: 300px;">{{ __('Filter Files') }}</button>
                  <button type="reset" class="btn btn-secondary w-100" style="max-width: 300px;">{{ __('Reset') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</section>

<!-- Modal -->
<div class="modal fade" id="eventModal" role="dialog" tabindex="-1" aria-labelledby="eventModalLabel" aria-modal="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق" autofocus></button>
      </div>
      <div class="modal-body">
        <p id="eventDescription"></p>
        <p id="eventDate" class="text-muted"></p>
      </div>
    </div>
  </div>
</div>

<section class="section-py bg-light" id="testimonials-2">
  <div class="container">
    @php
      $parents = $categories->whereNull('parent_id');
      $childrenByParent = $categories->whereNotNull('parent_id')->groupBy('parent_id');
    @endphp
    @php
      // Show only the first 6 parent categories in this grid
      $firstParents = $parents->values()->take(6);
      $parentChunks = $firstParents->chunk(3);
    @endphp
    @foreach($parentChunks as $group)
      <div class="row g-3 mb-3">
      @foreach($group as $category)
        @php
          // Determine if icon is a CSS class or an image path
          $iconClass = null;
          $iconPath = null;
          if (!empty($category->icon)) {
            $iconValue = $category->icon;
            $isAbsoluteUrl = Str::startsWith($iconValue, ['http://', 'https://', '//']);
            $looksLikePath = Str::contains($iconValue, ['/','\\']) || preg_match('/\.(png|jpe?g|gif|svg|webp)$/i', $iconValue);
            if ($isAbsoluteUrl) {
              $iconPath = $iconValue;
            } elseif ($looksLikePath) {
              $iconPath = asset('storage/' . ltrim($iconValue, '/'));
            } else {
              $iconClass = $iconValue;
            }
          }

          // Support explicit icon_image column as path
          if (!$iconPath && !empty($category->icon_image)) {
            $iconPath = Str::startsWith($category->icon_image, ['http://', 'https://', '//'])
              ? $category->icon_image
              : asset('storage/' . ltrim($category->icon_image, '/'));
          }

          // Fallback to category image
          $categoryImage = null;
          if (!empty($category->image)) {
            $categoryImage = Str::startsWith($category->image, ['http://', 'https://', '//'])
              ? $category->image
              : asset('storage/' . ltrim($category->image, '/'));
          }

          // Build optimized thumbs via img.fit when local storage assets are used
          $thumb44 = $thumb88 = null;
          $sourceForThumb = $iconPath ?: $categoryImage;
          if ($sourceForThumb && Str::startsWith($sourceForThumb, [asset('storage'), url('/storage')])) {
            $pathOnly = ltrim(parse_url($sourceForThumb, PHP_URL_PATH) ?? '', '/');
            $thumb44 = route('img.fit', ['size' => '44x44', 'path' => $pathOnly]);
            $thumb88 = route('img.fit', ['size' => '88x88', 'path' => $pathOnly]);
          }
        @endphp
        <div class="col-12 col-md-4">
          <a
            href="{{ route('content.frontend.posts.category', ['database' => $database ?? session('database', 'default_database'), 'category' => $category->id]) }}"
            class="w-100 d-flex align-items-center gap-3 mb-2 text-reset text-decoration-none border rounded-3 p-2 bg-white shadow-sm"
            aria-label="{{ $category->name }}"
          >
            @if($iconClass)
              <i class="{{ $iconClass }} fs-3 text-primary" aria-hidden="true"></i>
            @elseif($iconPath)
              @if($thumb44 && $thumb88)
                <img src="{{ $thumb44 }}" srcset="{{ $thumb44 }} 1x, {{ $thumb88 }} 2x" sizes="44px" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
              @else
                <img src="{{ $iconPath }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
              @endif
            @elseif($categoryImage)
              @if($thumb44 && $thumb88)
                <img src="{{ $thumb44 }}" srcset="{{ $thumb44 }} 1x, {{ $thumb88 }} 2x" sizes="44px" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
              @else
                <img src="{{ $categoryImage }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
              @endif
            @else
              <img src="{{ asset('assets/img/pages/post-default.webp') }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
            @endif
            <span class="fw-semibold text-truncate flex-grow-1">{{ $category->name }}</span>
            <i class="ti tabler-chevron-left text-muted"></i>
          </a>
          @if($childrenByParent->has($category->id))
            <div class="d-flex flex-wrap gap-1 mt-1">
              @foreach($childrenByParent[$category->id] as $child)
                <a
                  href="{{ route('content.frontend.posts.category', ['database' => $database ?? session('database', 'default_database'), 'category' => $child->id]) }}"
                  class="badge bg-light text-secondary border"
                  aria-label="{{ $child->name }}"
                >
                  {{ $child->name }}
                </a>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
      </div>
    @endforeach
  </div>

  <div class="container mt-4">
    <x-adsense.banner desktop-key="google_ads_desktop_home" mobile-key="google_ads_mobile_home" class="my-4" />
  </div>
</section>

<div class="container mt-4">
    @php
      // Use parent categories starting from the 7th to avoid duplication with the top grid
      $allParents = $categories->whereNull('parent_id')->values();
      $parentCats = $allParents->slice(6);
      $chunks = $parentCats->chunk(3);
    @endphp
    @foreach($chunks as $row)
      <div class="row g-3 mb-3">
        @foreach($row as $category)
          @php
            // Determine if icon is a CSS class or an image path for this category
            $iconClass = null;
            $iconPath = null;
            if (!empty($category->icon)) {
              $iconValue = $category->icon;
              $isAbsoluteUrl = Str::startsWith($iconValue, ['http://', 'https://', '//']);
              $looksLikePath = Str::contains($iconValue, ['/','\\']) || preg_match('/\.(png|jpe?g|gif|svg|webp)$/i', $iconValue);
              if ($isAbsoluteUrl) {
                $iconPath = $iconValue;
              } elseif ($looksLikePath) {
                $iconPath = asset('storage/' . ltrim($iconValue, '/'));
              } else {
                $iconClass = $iconValue;
              }
            }

            // Support explicit icon_image column as path
            if (!$iconPath && !empty($category->icon_image)) {
              $iconPath = Str::startsWith($category->icon_image, ['http://', 'https://', '//'])
                ? $category->icon_image
                : asset('storage/' . ltrim($category->icon_image, '/'));
            }

            // Fallback to category image
            $categoryImage = null;
            if (!empty($category->image)) {
              $categoryImage = Str::startsWith($category->image, ['http://', 'https://', '//'])
                ? $category->image
                : asset('storage/' . ltrim($category->image, '/'));
            }

            // Build optimized thumbs via img.fit when local storage assets are used
            $thumb44 = $thumb88 = null;
            $sourceForThumb = $iconPath ?: $categoryImage;
            if ($sourceForThumb && Str::startsWith($sourceForThumb, [asset('storage'), url('/storage')])) {
              $pathOnly = ltrim(parse_url($sourceForThumb, PHP_URL_PATH) ?? '', '/');
              $thumb44 = route('img.fit', ['size' => '44x44', 'path' => $pathOnly]);
              $thumb88 = route('img.fit', ['size' => '88x88', 'path' => $pathOnly]);
            }
          @endphp
          <div class="col-12 col-md-4">
            <a
              href="{{ route('content.frontend.posts.category', ['database' => $database ?? session('database', 'default_database'), 'category' => $category->id]) }}"
              class="w-100 d-flex align-items-center gap-3 mb-2 text-reset text-decoration-none border rounded-3 p-2 bg-white shadow-sm"
              aria-label="{{ $category->name }}"
            >
              @if($iconClass)
                <i class="{{ $iconClass }} fs-3 text-primary" aria-hidden="true"></i>
              @elseif($iconPath)
                @if($thumb44 && $thumb88)
                  <img src="{{ $thumb44 }}" srcset="{{ $thumb44 }} 1x, {{ $thumb88 }} 2x" sizes="44px" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
                @else
                  <img src="{{ $iconPath }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
                @endif
              @elseif($categoryImage)
                @if($thumb44 && $thumb88)
                  <img src="{{ $thumb44 }}" srcset="{{ $thumb44 }} 1x, {{ $thumb88 }} 2x" sizes="44px" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
                @else
                  <img src="{{ $categoryImage }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
                @endif
              @else
                <img src="{{ asset('assets/img/pages/post-default.webp') }}" alt="" aria-hidden="true" role="presentation" decoding="async" fetchpriority="low" loading="lazy" width="44" height="44" class="rounded object-fit-cover" style="object-fit: cover;">
              @endif
              <span class="fw-semibold text-truncate flex-grow-1">{{ $category->name }}</span>
              <i class="ti tabler-chevron-left text-muted"></i>
            </a>
            @if(isset($childrenByParent) && $childrenByParent->has($category->id))
              <div class="d-flex flex-wrap gap-1 mt-1">
                @foreach($childrenByParent[$category->id] as $child)
                  <a
                    href="{{ route('content.frontend.posts.category', ['database' => $database ?? session('database', 'default_database'), 'category' => $child->id]) }}"
                    class="badge bg-light text-secondary border"
                    aria-label="{{ $child->name }}"
                  >
                    {{ $child->name }}
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endforeach
  
</div>


@endsection
