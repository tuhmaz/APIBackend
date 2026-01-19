@php
$database = session('database', 'jo');
$filterUrl = route('content.frontend.posts.filter', ['database' => $database]);
use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutFront')

@section('title', __('Posts'))

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/animate-css/animate.scss',
])
@endsection

@section('content')
<!-- Hero Section with Project Style -->
<section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
  <!-- Background Pattern -->
  <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

  <!-- Animated Shapes -->
  <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
  <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

  <div class="container position-relative">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8 text-center">

        <!-- Main Title -->
        <h1 class="display-4 text-white mb-4" style="font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
          {{ __('Posts') }}
        </h1>

        <!-- Subtitle -->
        <p class="text-white mb-4 opacity-75" style="font-size: 1.1rem;">
          {{ __('Explore articles, news, and insights from our community') }}
        </p>

        <!-- Search Bar -->
        <div class="search-wrapper">
          <form action="{{ route('content.frontend.posts.index', ['database' => $database]) }}" method="GET" class="position-relative">
            <div class="input-group input-group-lg shadow-lg" style="border-radius: 50px; overflow: hidden;">
              <span class="input-group-text bg-white border-0 ps-4">
                <i class="post-icon ti tabler-search text-muted"></i>
              </span>
              <input
                type="text"
                name="keyword"
                class="form-control border-0 ps-2"
                placeholder="{{ __('Search for posts...') }}"
                value="{{ request('keyword') }}"
                style="font-size: 1rem;">
              <button class="btn btn-primary px-5" type="submit" style="border-radius: 0 50px 50px 0; background: linear-gradient(45deg, #3498db, #2980b9); border: none;">
                {{ __('Search') }}
              </button>
            </div>
          </form>
        </div>

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
  <ol class="breadcrumb breadcrumb-style2" aria-label="breadcrumbs">
    <li class="breadcrumb-item">
      <a href="{{ route('home') }}">
        <i class="post-icon ti tabler-home me-1"></i>{{ __('Home') }}
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Posts') }}</li>
  </ol>
</div>

<!-- Main Content Section -->
<section class="section-py bg-body" style="padding-top: 10px;">
  <div class="container">
    <div class="row g-4">

      <!-- Sidebar with Categories -->
      <div class="col-lg-3 col-md-4">
        <div class="sidebar-sticky" style="position: sticky; top: 100px;">
          <!-- Categories Card -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
              <h5 class="mb-0 text-white d-flex align-items-center">
                <i class="post-icon ti tabler-category me-2"></i>
                {{ __('Categories') }}
              </h5>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <!-- All Categories -->
                <a href="{{ route('content.frontend.posts.index', ['database' => $database]) }}"
                  class="list-group-item list-group-item-action border-0 d-flex justify-content-between align-items-center {{ !request()->has('category') ? 'active' : '' }}">
                  <span><i class="post-icon ti tabler-apps me-2"></i>{{ __('All Categories') }}</span>
                  <span class="badge rounded-pill {{ !request()->has('category') ? 'bg-white text-primary' : 'bg-light text-dark' }}">
                    {{ number_format($totalPosts ?? 0) }}
                  </span>
                </a>

                @php
                  $parents = $categories->whereNull('parent_id');
                  $childrenByParent = $categories->whereNotNull('parent_id')->groupBy('parent_id');
                @endphp

                @foreach($parents as $parent)
                  <a href="{{ route('content.frontend.posts.index', ['database' => $database, 'category' => $parent->slug]) }}"
                    class="list-group-item list-group-item-action border-0 d-flex justify-content-between align-items-center {{ request()->input('category') == $parent->slug ? 'active' : '' }}">
                    <span><i class="post-icon ti tabler-folder me-2"></i>{{ $parent->name }}</span>
                    <span class="badge rounded-pill {{ request()->input('category') == $parent->slug ? 'bg-white text-primary' : 'bg-light text-dark' }}">
                      {{ number_format($parent->posts_count ?? 0) }}
                    </span>
                  </a>

                  @if($childrenByParent->has($parent->id))
                    @foreach($childrenByParent[$parent->id] as $child)
                      <a href="{{ route('content.frontend.posts.index', ['database' => $database, 'category' => $child->slug]) }}"
                        class="list-group-item list-group-item-action border-0 ps-5 d-flex justify-content-between align-items-center {{ request()->input('category') == $child->slug ? 'active' : '' }}"
                        style="font-size: 0.9rem;">
                        <span><i class="post-icon ti tabler-point me-2"></i>{{ $child->name }}</span>
                        <span class="badge rounded-pill {{ request()->input('category') == $child->slug ? 'bg-white text-primary' : 'bg-light text-dark' }}">
                          {{ number_format($child->posts_count ?? 0) }}
                        </span>
                      </a>
                    @endforeach
                  @endif
                @endforeach
              </div>
            </div>
          </div>

          <!-- Stats Card -->
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0">
                  <div class="avatar">
                    <div class="avatar-initial rounded bg-label-primary">
                      <i class="post-icon ti tabler-files ti-md"></i>
                    </div>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h5 class="mb-0">{{ number_format($totalPosts ?? 0) }}</h5>
                  <small class="text-muted">{{ __('Total Posts') }}</small>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                  <div class="avatar">
                    <div class="avatar-initial rounded bg-label-success">
                      <i class="post-icon ti tabler-category ti-md"></i>
                    </div>
                  </div>
                </div>
                <div class="flex-grow-1 ms-3">
                  <h5 class="mb-0">{{ $categories->count() }}</h5>
                  <small class="text-muted">{{ __('Categories') }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Posts Grid -->
      <div class="col-lg-9 col-md-8">
        <!-- Filter & Sort Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="mb-0 fw-bold">
            @if(request()->has('category'))
              {{ __('Filtered Posts') }}
            @else
              {{ __('All Posts') }}
            @endif
            <span class="text-muted fw-normal" style="font-size: 1rem;">({{ $posts->total() }})</span>
          </h4>

          <div class="btn-group shadow-sm">
            <button type="button" class="btn btn-outline-secondary active" data-view="grid" title="{{ __('Grid View') }}">
              <i class="post-icon ti tabler-layout-grid"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-view="list" title="{{ __('List View') }}">
              <i class="post-icon ti tabler-list"></i>
            </button>
          </div>
        </div>

        <!-- Posts Container -->
        <div class="row g-4" id="posts-container">
          @forelse($posts as $index => $item)
            <div class="col-lg-6 col-md-12 post-item">
              <article class="card post-card h-100 border-0 shadow-sm overflow-hidden">
                <!-- Post Image -->
                @if($item->image)
                  <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $item->id]) }}" class="text-decoration-none d-block">
                    <div class="position-relative post-image-wrapper" style="height: 240px; overflow: hidden; cursor: pointer;">
                      <img
                        src="{{ $item->image_url }}"
                        class="card-img-top w-100 h-100 post-image"
                        alt="{{ $item->alt ?? $item->title }}"
                        loading="lazy"
                        style="object-fit: cover;"
                        onerror="this.src='{{ asset('assets/img/illustrations/default_news_image.jpg') }}'">

                      <!-- Category Badge -->
                      @if($item->category)
                        <span class="position-absolute top-0 end-0 m-3 badge bg-primary shadow-sm">
                          {{ $item->category->name }}
                        </span>
                      @endif

                      <!-- Featured Badge -->
                      @if($item->is_featured)
                        <span class="position-absolute top-0 start-0 m-3 badge bg-warning shadow-sm">
                          <i class="post-icon ti tabler-star me-1"></i>{{ __('Featured') }}
                        </span>
                      @endif
                    </div>
                  </a>
                @endif

                <!-- Post Content -->
                <div class="card-body d-flex flex-column">
                  <!-- Post Meta -->
                  <div class="d-flex align-items-center gap-3 mb-3 text-muted small">
                    <span class="d-flex align-items-center">
                      <i class="post-icon ti tabler-calendar me-1"></i>
                      {{ $item->created_at->format('M d, Y') }}
                    </span>
                    @if($item->views)
                      <span class="d-flex align-items-center">
                        <i class="post-icon ti tabler-eye me-1"></i>
                        {{ number_format($item->views) }}
                      </span>
                    @endif
                  </div>

                  <!-- Post Title -->
                  <h5 class="card-title mb-3 fw-bold">
                    <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $item->id]) }}"
                      class="text-dark text-decoration-none post-title-link">
                      {{ Str::limit($item->title, 80) }}
                    </a>
                  </h5>

                  <!-- Post Excerpt -->
                  <p class="card-text text-muted mb-0 flex-grow-1">
                    {{ Str::limit(strip_tags($item->content), 120) }}
                  </p>
                </div>
              </article>
            </div>
          @empty
            <!-- Empty State -->
            <div class="col-12">
              <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                  <div class="mb-4">
                    <i class="post-icon ti tabler-article-off text-muted" style="font-size: 5rem;"></i>
                  </div>
                  <h4 class="text-muted mb-3">{{ __('No Posts Found') }}</h4>
                  <p class="text-muted mb-4">{{ __('Try adjusting your search or filter criteria') }}</p>
                  <a href="{{ route('content.frontend.posts.index', ['database' => $database]) }}"
                     class="btn btn-primary"
                     style="background: linear-gradient(45deg, #3498db, #2980b9); border: none;">
                    <i class="post-icon ti tabler-refresh me-2"></i>{{ __('Show All Posts') }}
                  </a>
                </div>
              </div>
            </div>
          @endforelse
        </div>

        <!-- Pagination -->
        @if($posts->hasPages())
          <div class="d-flex justify-content-center mt-5">
            <nav aria-label="Posts pagination">
              {{ $posts->links('pagination::bootstrap-5') }}
            </nav>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection

@push('page-style')
<style>
/* Posts Container */
#posts-container {
  transition: opacity 0.3s ease;
}

/* Post Card Styles */
.post-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.post-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
}

/* Post Image Hover Effect */
.post-image {
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.post-card:hover .post-image {
  transform: scale(1.1);
}

/* Post Title Link Hover */
.post-title-link {
  transition: color 0.2s ease;
}

.post-title-link:hover {
  color: #3498db !important;
}

/* Sidebar Categories */
.list-group-item {
  transition: all 0.2s ease;
  cursor: pointer;
}

.list-group-item:hover:not(.active) {
  background-color: #f8f9fa;
  padding-left: 1.25rem !important;
}

.list-group-item.active {
  background: linear-gradient(226deg, #202c45 0%, #286aad 100%);
  border-color: transparent;
  color: white;
}

/* Search Bar */
.search-wrapper .input-group {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.search-wrapper .input-group:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
}

/* View Switcher */
.btn-group button {
  transition: all 0.2s ease;
}

.btn-group button.active {
  background-color: #3498db;
  color: white;
  border-color: #3498db;
}

/* List View Styles */
#posts-container.list-view .post-item {
  width: 100% !important;
  max-width: 100% !important;
  flex: 0 0 100% !important;
}

#posts-container.list-view .post-card {
  display: flex !important;
  flex-direction: row !important;
}

#posts-container.list-view .post-image-wrapper {
  width: 350px !important;
  min-width: 350px !important;
  height: auto !important;
  min-height: 280px !important;
  flex-shrink: 0 !important;
}

#posts-container.list-view .card-body {
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
}

#posts-container.list-view .post-card .post-image {
  height: 100% !important;
}

#posts-container.list-view .card-title {
  font-size: 1.25rem !important;
}

#posts-container.list-view .card-text {
  display: -webkit-box !important;
  -webkit-line-clamp: 3 !important;
  -webkit-box-orient: vertical !important;
  overflow: hidden !important;
}

/* Responsive adjustments */
@media (max-width: 992px) {
  .sidebar-sticky {
    position: relative !important;
    top: 0 !important;
  }
}

@media (max-width: 768px) {
  #posts-container.list-view .post-card {
    flex-direction: column !important;
  }

  #posts-container.list-view .post-image-wrapper {
    width: 100% !important;
    min-width: 100% !important;
    height: 240px !important;
    min-height: 240px !important;
  }

  #posts-container.list-view .post-card .post-image {
    height: 240px !important;
  }
}

/* Badge Animations */
.badge {
  animation: fadeInScale 0.3s ease;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>
@endpush

@push('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // View Switcher (Grid/List)
  const viewButtons = document.querySelectorAll('[data-view]');
  const postsContainer = document.getElementById('posts-container');

  // Load saved view preference from localStorage
  const savedView = localStorage.getItem('postsViewMode') || 'grid';

  // Apply saved view on page load
  if (savedView === 'list') {
    postsContainer.classList.add('list-view');
    viewButtons.forEach(btn => {
      if (btn.dataset.view === 'list') {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  // View switcher click handler
  viewButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      // Remove active class from all buttons
      viewButtons.forEach(b => b.classList.remove('active'));

      // Add active class to clicked button
      this.classList.add('active');

      const view = this.dataset.view;

      // Apply view mode
      if (view === 'list') {
        postsContainer.classList.add('list-view');
        localStorage.setItem('postsViewMode', 'list');
      } else {
        postsContainer.classList.remove('list-view');
        localStorage.setItem('postsViewMode', 'grid');
      }

      // Smooth transition effect
      postsContainer.style.opacity = '0.7';
      setTimeout(() => {
        postsContainer.style.opacity = '1';
      }, 150);
    });
  });

  // Image Lazy Loading Fallback
  const images = document.querySelectorAll('img[loading="lazy"]');
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          observer.unobserve(img);
        }
      });
    });

    images.forEach(img => imageObserver.observe(img));
  }
});
</script>
@endpush
