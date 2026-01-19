@extends('layouts.layoutFront')

@section('title', __('all_keywords'))



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

                <!-- Main Title with Animation -->
                <h2 class="display-6 text-white mb-4 animate__animated animate__fadeInDown" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                {{ __('all_keywords') }}
                <p class="text-center text-white px-4 mb-0" style="font-size: medium;">{{ __('explore_keywords') }}</p>

                </h2>


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

<div class="container px-4 mt-4">
  <ol class="breadcrumb breadcrumb-style2" aria-label="breadcrumbs">
    <li class="breadcrumb-item">
      <a href="{{ route('home') }}">
        <i class="article-icon ti tabler-home-check"></i>{{ __('home') }}
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('keywords') }}</li>
  </ol>
  <div class="progress mt-2">
    <div class="progress-bar" role="progressbar" style="width: 100%;"></div>
  </div>
</div>

<section class="section-py bg-body first-section-pt" style="padding-top: 10px;">
  <div class="container">
    <!-- Filters -->
    <form method="GET" action="{{ url()->current() }}" class="card mb-4 shadow-sm">
      <div class="card-body row g-3 align-items-end">
        <div class="col-12 col-md-6 col-lg-8">
          <label for="q" class="form-label">{{ __('search') }}</label>
          <input type="text" id="q" name="q" class="form-control" value="{{ request('q','') }}" placeholder="{{ __('search') }}...">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label for="per_page" class="form-label">{{ __('per_page') }}</label>
          <select id="per_page" name="per_page" class="form-select">
            @foreach([24,60,96,120] as $pp)
              <option value="{{ $pp }}" {{ (int)request('per_page',60)===$pp ? 'selected' : '' }}>{{ $pp }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2 d-grid">
          <button type="submit" class="btn btn-primary">{{ __('apply') }}</button>
        </div>
      </div>
    </form>

    <!-- Tabs -->
    @php $activeTab = request('tab','articles'); @endphp
    <ul class="nav nav-pills mb-3" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab==='articles' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-article-kw" type="button" role="tab">{{ __('article_keywords') }}</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab==='posts' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-post-kw" type="button" role="tab">{{ __('posts_keywords') }}</button>
      </li>
    </ul>

    <div class="tab-content">
      <!-- Article Keywords -->
      <div class="tab-pane fade {{ $activeTab==='articles' ? 'show active' : '' }}" id="tab-article-kw" role="tabpanel">
        <div class="card px-3 shadow-sm">
          <div class="content-body text-center mt-3">
            @if($articleKeywords->count())
            <div class="keywords-cloud">
              @foreach($articleKeywords as $keyword)
              @continue(empty($keyword->keyword))
              <a href="{{ route('keywords.indexByKeyword', ['database' => $database ?? session('database', 'jo'), 'keywords' => $keyword->keyword]) }}" class="keyword-item btn btn-outline-secondary m-1">
                {{ $keyword->keyword }}
                @isset($keyword->items_count)
                <span class="badge bg-primary ms-1">{{ $keyword->items_count }}</span>
                @endisset
              </a>
              @endforeach
            </div>
            <div class="d-flex justify-content-center py-3">
              {{ $articleKeywords->appends(array_merge(request()->query(), ['tab' => 'articles']))->links() }}
            </div>
            @else
            <p>{{ __('no_article_keywords') }}</p>
            @endif
          </div>
        </div>
      </div>

      <!-- Post Keywords -->
      <div class="tab-pane fade {{ $activeTab==='posts' ? 'show active' : '' }}" id="tab-post-kw" role="tabpanel">
        <div class="card px-3 shadow-sm">
          <div class="content-body text-center mt-3">
            @if($postKeywords->count())
            <div class="keywords-cloud">
              @foreach($postKeywords as $keyword)
              @continue(empty($keyword->keyword))
              <a href="{{ route('keywords.indexByKeyword', ['database' => $database ?? session('database', 'jo'), 'keywords' => $keyword->keyword]) }}" class="keyword-item btn btn-outline-secondary m-1">
                {{ $keyword->keyword }}
                @isset($keyword->items_count)
                <span class="badge bg-primary ms-1">{{ $keyword->items_count }}</span>
                @endisset
              </a>
              @endforeach
            </div>
            <div class="d-flex justify-content-center py-3">
              {{ $postKeywords->appends(array_merge(request()->query(), ['tab' => 'posts']))->links() }}
            </div>
            @else
            <p>{{ __('no_posts_keywords') }}</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
