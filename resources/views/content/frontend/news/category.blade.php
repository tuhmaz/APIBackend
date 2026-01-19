@extends('layouts.layoutFront')

@section('title', $category->name . ' - ' . __('Posts'))

@section('content')
@php
  // Detect icon as class or image path (consistent with home page logic)
  $iconClass = null;
  $iconPath = null;
  if (!empty($category->icon)) {
    $iconValue = $category->icon;
    $looksLikePath = \Illuminate\Support\Str::contains($iconValue, ['/', '\\']) || preg_match('/\.(png|jpe?g|gif|svg|webp)$/i', $iconValue);
    if ($looksLikePath) {
      $iconPath = asset('storage/' . ltrim($iconValue, '/'));
    } else {
      $iconClass = $iconValue;
    }
  }
  if (!$iconPath && !empty($category->icon_image)) {
    $iconPath = asset('storage/' . ltrim($category->icon_image, '/'));
  }
  $categoryImage = !empty($category->image) ? asset('storage/' . ltrim($category->image, '/')) : null;
@endphp

<!-- Hero Header -->
<section class="section-py first-section-pt position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
  <!-- Subtle overlay -->
  <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.08), transparent);"></div>

  <div class="container position-relative py-5">
    <div class="row align-items-center g-4">
      <div class="col-12 col-md-auto d-flex align-items-center justify-content-center">
        @if($iconClass)
          <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center shadow" style="width:72px;height:72px;">
            <i class="{{ $iconClass }} text-primary" style="font-size: 34px;"></i>
          </div>
        @elseif($iconPath || $categoryImage)
          <img src="{{ $iconPath ?: $categoryImage }}" alt="{{ $category->name }}" loading="lazy" class="rounded-circle shadow" style="width:72px;height:72px;object-fit:cover;">
        @else
          <img src="{{ asset('assets/img/pages/post-default.webp') }}" alt="{{ $category->name }}" loading="lazy" class="rounded-circle shadow" style="width:72px;height:72px;object-fit:cover;">
        @endif
      </div>
      <div class="col">
        <nav aria-label="breadcrumb" class="mb-2">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a class="text-white-50" href="{{ route('content.frontend.posts.index', ['database' => $database]) }}">{{ __('Posts') }}</a></li>
            <li class="breadcrumb-item active text-white" aria-current="page">{{ $category->name }}</li>
          </ol>
        </nav>
        <h1 class="h2 text-white mb-1" style="font-weight:600;">{{ $category->name }}</h1>
        <p class="text-white-50 mb-0">{{ __('Latest posts and updates in this category') }}</p>
      </div>
    </div>
  </div>

  <!-- Wave divider -->
  <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 48px;">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width:100%;height:48px;transform: rotate(180deg);">
      <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #ffffff;"></path>
    </svg>
  </div>
</section>

<!-- Breadcrumb + Progress -->
<div class="container px-4 mt-4">
  @if(isset($children) && $children->count())
    <div class="mb-3 d-flex flex-wrap gap-2">
      @foreach($children as $child)
        <a
          href="{{ route('content.frontend.posts.category', ['database' => $database, 'category' => $child->slug]) }}"
          class="badge bg-light text-secondary border"
          aria-label="{{ $child->name }}"
        >
          {{ $child->name }}
        </a>
      @endforeach
    </div>
  @endif
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-style2">
      <li class="breadcrumb-item">
        <a href="{{ route('home') }}" class="text-decoration-none">
          <i class="post-icon ti tabler-home-check"></i> {{ __('Home') }}
        </a>
      </li>
      <li class="breadcrumb-item">
        <a href="{{ route('content.frontend.posts.index', ['database' => $database]) }}" class="text-decoration-none">
          {{ __('Posts') }}
        </a>
      </li>
      <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
    </ol>
  </nav>
  @php
    $current = method_exists($posts, 'currentPage') ? $posts->currentPage() : 1;
    $last = method_exists($posts, 'lastPage') ? max($posts->lastPage(), 1) : 1;
    $progress = intval(($current / $last) * 100);
  @endphp
  <div class="progress mt-2" style="height: 6px;">
    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
  </div>
</div>

<!-- Content -->
<div class="container py-5">
  <div class="row g-4">
    @forelse($posts as $item)
      <div class="col-sm-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
          @php
            $img = $item->image ? asset('storage/' . ltrim($item->image, '/')) : asset('assets/img/pages/post-default.webp');
          @endphp
          <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $item->id]) }}" class="d-block">
            <img src="{{ $img }}" class="card-img-top" alt="{{ $item->title }}" style="height: 200px; object-fit: cover;">
          </a>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title mb-2 text-truncate" title="{{ $item->title }}">
              <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $item->id]) }}" class="text-body text-decoration-none">
                {{ $item->title }}
              </a>
            </h5>
            <p class="card-text text-muted mb-3" style="min-height: 48px;">{{ \Illuminate\Support\Str::limit($item->excerpt, 150) }}</p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $item->id]) }}" class="btn btn-primary btn-sm">
                {{ __('Read More') }}
              </a>
              <small class="text-muted">{{ $item->created_at->diffForHumans() }}</small>
            </div>
          </div>
        </div>

      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-info mb-0">
          {{ __('No posts available in this category.') }}
        </div>
      </div>
    @endforelse
  </div>

  @if(method_exists($posts, 'links'))
  <div class="row mt-4">
    <div class="col-12 d-flex justify-content-center">
      {{ $posts->withQueryString()->links() }}
    </div>
  </div>
  @endif
</div>
@endsection
