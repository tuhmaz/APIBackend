@php
  $database = session('database', 'jo');
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutFront')

@section('title', $category->name)

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
            {{ $category->name }}
          </h1>
          @if(!empty($category->description))
            <p class="text-white-50 mb-0">{{ Str::limit(strip_tags($category->description), 160) }}</p>
          @endif
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

  <!-- Breadcrumb -->
  <div class="container px-4 mt-4">
    <ol class="breadcrumb breadcrumb-style2 mb-4">
      <li class="breadcrumb-item">
        <a href="{{ route('home') }}" class="text-decoration-none">
          <i class="ti tabler-home-check"></i> {{ __('Home') }}
        </a>
      </li>
      <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
    </ol>
  </div>

  <!-- Main Content -->
  <section class="section-py bg-body">
    <div class="container">
      <div class="row g-4">
        <div class="col-12">
          <div class="card shadow-sm hover-elevate-up border-0">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <i class="ti tabler-category text-primary me-2" style="font-size: 1.5rem;"></i>
                <h3 class="mb-0">{{ $category->name }}</h3>
              </div>
              @if(!empty($category->description))
                <p class="text-muted mb-4">{{ $category->description }}</p>
              @endif

              <a href="{{ route('content.frontend.posts.index', ['database' => $database, 'category' => $category->slug]) }}" class="btn btn-primary">
                <i class="ti tabler-list-details me-1"></i>{{ __('View posts in this category') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
