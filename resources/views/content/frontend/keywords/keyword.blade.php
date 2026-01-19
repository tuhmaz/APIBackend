{{-- resources/views/frontend/keyword/keyword.blade.php --}}
@extends('layouts/layoutFront')

@php
$configData = Helper::appClasses();
$pageTitle = __('content_related_to', ['keyword' => $keyword->keyword]);
use Illuminate\Support\Str;

@endphp

@section('title', $pageTitle)

@section('page-style')
@vite('resources/assets/vendor/scss/pages/front-page-help-center.scss')
@endsection

@section('meta')
<meta name="keywords" content="{{ $keyword->keyword }}">

<meta name="description" content="{{ __('find_articles_news_related_to', ['keyword' => $keyword->keyword]) }}">

<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:title" content="{{ __('content_related_to', ['keyword' => $keyword->keyword]) }}" />
<meta property="og:description" content="{{ __('find_articles_news_related_to', ['keyword' => $keyword->keyword]) }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ $ogImage ?? asset('assets/img/front-pages/icons/articles_default_image.webp') }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ __('content_related_to', ['keyword' => $keyword->keyword]) }}" />
<meta name="twitter:description" content="{{ __('find_articles_news_related_to', ['keyword' => $keyword->keyword]) }}" />
<meta name="twitter:image" content="{{ $ogImage ?? asset('assets/img/front-pages/icons/articles_default_image.webp') }}" />
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

                <!-- Main Title with Animation -->
                <h2 class="display-6 text-white mb-4 animate__animated animate__fadeInDown" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                {{ __('content_related_to') }} {{ $keyword->keyword }}

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
        <i class="home-icon ti tabler-home-check"></i>{{ __('home') }}
      </a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('frontend.keywords.index', ['database' => $database ?? session('database', 'jo')]) }}">{{ __('keywords') }}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ $keyword->keyword }}</li>
  </ol>
  <div class="progress mt-2">
    <div class="progress-bar" role="progressbar" style="width: 80%;"></div>
  </div>
</div>

<div class="container mt-4">
  @if(($articles instanceof \Illuminate\Contracts\Pagination\Paginator && $articles->isEmpty()) && ($posts instanceof \Illuminate\Contracts\Pagination\Paginator && $posts->isEmpty()))
    <p class="text-center">{{ __('no_content_for_keyword') }}</p>
  @else
  <!-- Filters -->
  <form method="GET" action="{{ url()->current() }}" class="card mb-4 shadow-sm">
    <div class="card-body row g-3 align-items-end">
      <div class="col-12 col-md-6 col-lg-6">
        <label class="form-label" for="q">{{ __('search') }}</label>
        <input type="text" class="form-control" id="q" name="q" value="{{ request('q','') }}" placeholder="{{ __('search') }}...">
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label" for="sort">{{ __('sort') }}</label>
        <select class="form-select" id="sort" name="sort">
          <option value="latest" {{ request('sort','latest')==='latest' ? 'selected' : '' }}>{{ __('latest') }}</option>
          <option value="title" {{ request('sort')==='title' ? 'selected' : '' }}>{{ __('title') }}</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label" for="per_page">{{ __('per_page') }}</label>
        <select class="form-select" id="per_page" name="per_page">
          @foreach([12,24,36,48] as $pp)
            <option value="{{ $pp }}" {{ (int)request('per_page',12)===$pp ? 'selected' : '' }}>{{ $pp }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-lg-2 d-grid">
        <button type="submit" class="btn btn-primary">{{ __('apply') }}</button>
      </div>
    </div>
  </form>

  <!-- Tabs -->
  @php $activeTab = request('tab','articles'); @endphp
  <ul class="nav nav-pills mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab==='articles' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-articles" type="button" role="tab">{{ __('articles') }}</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab==='posts' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-posts" type="button" role="tab">{{ __('posts') }}</button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- Articles Tab -->
    <div class="tab-pane fade {{ $activeTab==='articles' ? 'show active' : '' }}" id="tab-articles" role="tabpanel">
      <div class="row">
        @forelse($articles as $article)
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
          <div class="card h-100 d-flex flex-column shadow-sm">
            <img src="{{ $article->image_url ?? asset('assets/img/front-pages/icons/articles_default_image.webp') }}" loading="lazy" decoding="async" class="card-img-top" alt="{{ $article->title }}" style="height: 180px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
              <h6 class="card-title mb-2 text-truncate" title="{{ $article->title }}">{{ $article->title }}</h6>
              <p class="card-text small text-muted">{{ Str::limit(strip_tags($article->content), 120) }}</p>
              <div class="mt-auto">
                <a href="{{ route('frontend.articles.show', ['database' => $database ?? session('database', 'jo'), 'article' => $article->id]) }}" class="btn btn-sm btn-primary">{{ __('read_more') }}</a>
              </div>
            </div>
          </div>
        </div>
        @empty
          <p class="text-center">{{ __('no_content_for_keyword') }}</p>
        @endforelse
      </div>
      <div class="d-flex justify-content-center">
        {{ $articles->appends(array_merge(request()->query(), ['tab' => 'articles']))->links() }}
      </div>
    </div>

    <!-- Posts Tab -->
    <div class="tab-pane fade {{ $activeTab==='posts' ? 'show active' : '' }}" id="tab-posts" role="tabpanel">
      <div class="row">
        @forelse($posts as $post)
        <div class="col-12 col-sm-6 col-lg-3 mb-4">
          <div class="card h-100 d-flex flex-column shadow-sm">
            @php
              $imagePath = $post->image ? asset('storage/' . $post->image) : asset('assets/img/pages/news-default.jpg');
            @endphp
            <img src="{{ $imagePath }}" loading="lazy" decoding="async" class="card-img-top" alt="{{ $post->title }}" style="height: 180px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
              <h6 class="card-title mb-2 text-truncate" title="{{ $post->title }}">{{ $post->title }}</h6>
              <p class="card-text small text-muted">{{ Str::limit(strip_tags($post->description), 120) }}</p>
              <div class="mt-auto">
                <a href="{{ route('content.frontend.posts.show', ['database' => $database ?? session('database', 'jo'), 'id' => $post->id]) }}" class="btn btn-sm btn-primary">{{ __('read_more') }}</a>
              </div>
            </div>
          </div>
        </div>
        @empty
          <p class="text-center">{{ __('no_content_for_keyword') }}</p>
        @endforelse
      </div>
      <div class="d-flex justify-content-center">
        {{ $posts->appends(array_merge(request()->query(), ['tab' => 'posts']))->links() }}

      </div>
    </div>
  </div>
  @endif
</div>
@endsection
