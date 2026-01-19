@php
  $database = session('database', 'jo');
@endphp

@extends('layouts/layoutFront')

@section('title', __('Categories'))

@section('content')
  <!-- Hero Section -->
  <section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
    <!-- Background Pattern -->
    <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

    <!-- Animated Shapes -->
    <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
    <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

    <div class="container position-relative">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8 text-center">
          <h1 class="display-6 text-white mb-3" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            {{ __('Categories') }}
          </h1>
          <p class="text-white-50 mb-0">{{ __('Browse all active categories and explore related posts') }}</p>
        </div>
      </div>
    </div>

    <!-- Wave Shape Divider -->
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
      <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
        <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70-5.51,136.38-33.31,206.8-37.5C438.64,32.05,512.34,53.5,583,72.05c69.27,18.19,138.3,24.88,209,13.08C936.78,66.49,1002.4,39.57,1071,21.31c43.61-11.6,86.86-12.8,129,0V0Z" opacity=".25" fill="#ffffff"></path>
        <path d="M0,0V15.81C13,36.46,27.64,56.37,47,69.95c55.27,40,136.76,39.2,195,1.39,35-22.65,62.88-56.8,103-74,53-23,117-17,172-0.84,31,9.24,60.85,22,92,30.23,83,21.55,176,15.4,250-22.59C983,31,1043-2.38,1116,0.49c31,1.17,61,7.09,84,13.51V0Z" opacity=".5" fill="#ffffff"></path>
        <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,4.25,165.56,27.35C827.93,64,886,84.37,951,85.64c86,.64,172-19.25,249-53.61V0Z" fill="#ffffff"></path>
      </svg>
    </div>
  </section>

  <!-- Content Section -->
  <div class="container section-py">
    <div class="row g-4">
      @forelse($categories as $category)
        <div class="col-12 col-md-6 col-lg-4">
          <a href="{{ route('content.frontend.categories.show', $category->slug) }}" class="text-decoration-none">
            <div class="card h-100 hover-elevate-up">
              <div class="card-body d-flex align-items-start">
                <div class="avatar flex-shrink-0 me-3 bg-label-primary">
                  <i class="cat-icon ti tabler-folder ti-lg text-primary"></i>
                </div>
                <div class="flex-grow-1">
                  <h5 class="card-title text-body mb-1">{{ $category->name }}</h5>
                  <div class="small text-muted">{{ __('Posts') }}: {{ $category->posts_count }}</div>
                </div>
                <span class="ms-auto badge bg-primary">{{ __('View') }}</span>
              </div>
            </div>
          </a>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-info mb-0">{{ __('No categories found') }}</div>
        </div>
      @endforelse
    </div>
  </div>
@endsection
