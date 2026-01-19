@extends('layouts/layoutFront')

@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;

  // Normalize variables: allow controller to pass either $news or $post
  if (!isset($post) && isset($news)) {
    $post = $news;
  }

  // Ensure database is set
  $database = $database ?? session('database', 'default_database');
  // Get the author from the main database (jo)
  $author = isset($post) ? \App\Models\User::on('jo')->find($post->author_id) : null;
  if (isset($post)) {
    $post->setRelation('author', $author);
  }

  // Social locale map
  $localeMap = ['sa' => 'ar_SA', 'eg' => 'ar_EG', 'ps' => 'ar_PS', 'jo' => 'ar_JO'];
  $ogLocale = $localeMap[$database] ?? 'ar_JO';

  // Normalize twitter handle
  $configuredTwitter = trim((string) config('settings.social_twitter'));
  $twitterHandle = $configuredTwitter;
  if (Str::startsWith($configuredTwitter, ['http://twitter.com/', 'https://twitter.com/'])) {
    $twitterHandle = '@' . ltrim(parse_url($configuredTwitter, PHP_URL_PATH), '/');
  }
  if ($twitterHandle && $twitterHandle[0] !== '@') {
    $twitterHandle = '@' . $twitterHandle;
  }

  // Compute main image URL
  $rawImage = method_exists($post ?? null, 'imageUrl')
    ? $post->imageUrl()
    : (($post->image ?? null) ? asset('storage/' . ltrim($post->image, '/')) : asset('assets/img/front-pages/icons/articles_default_image.webp'));
  $ogImage = $rawImage;
  $ogImageSecure = preg_replace('#^http://#i', 'https://', $ogImage);

  // Prepare keywords array
  $__keywordsArray = is_string($post->keywords ?? null) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];

  // Compute related posts by category and keywords
  $relatedQuery = \App\Models\Post::on($database)
    ->where('id', '!=', $post->id ?? 0)
    ->where('is_active', true);
  if (!empty($post->category_id)) {
    $relatedQuery->where('category_id', $post->category_id);
  }
  if (!empty($__keywordsArray)) {
    $relatedQuery->where(function($q) use ($__keywordsArray) {
      foreach ($__keywordsArray as $kw) {
        $q->orWhere('title', 'like', "%$kw%")
          ->orWhere('keywords', 'like', "%$kw%");
      }
    });
  }
  $relatedNews = $relatedQuery->latest()->limit(6)->get();
  if ($relatedNews->isEmpty()) {
    $relatedNews = \App\Models\Post::on($database)->where('is_active', true)->inRandomOrder()->limit(6)->get();
  }
@endphp

@section('title', $post->title)
@section('meta_title', $post->title . ' - ' . ($post->meta_title ?? config('settings.meta_title')))

@section('meta')
@php
  $rawMeta = $post->meta_description ?? $post->excerpt ?? $post->content ?? '';
  $plain = strip_tags($rawMeta);
  $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $decoded = preg_replace('/\x{00A0}|&nbsp;/u', ' ', $decoded);
  $metaDescClean = trim(preg_replace('/\s+/u', ' ', $decoded));
  $keywordsArray = is_string($post->keywords) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];
@endphp
<meta name="keywords" content="{{ implode(',', $keywordsArray) }}">
<meta name="description" content="{{ Str::limit($metaDescClean, 160, '') }}">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="{{ url()->current() }}">

<!-- Open Graph -->
<meta property="og:title" content="{{ $post->title }}" />
<meta property="og:description" content="{{ Str::limit($metaDescClean, 200, '') }}" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ $ogImage }}" />
<meta property="og:image:secure_url" content="{{ $ogImageSecure }}" />
<meta property="og:image:alt" content="{{ $post->title }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:locale" content="{{ $ogLocale }}" />
<meta property="og:site_name" content="{{ config('settings.site_name', 'site_name') }}" />
<meta property="article:published_time" content="{{ optional($post->created_at)->toIso8601String() }}" />
<meta property="article:modified_time" content="{{ optional($post->updated_at)->toIso8601String() }}" />
<meta property="article:section" content="{{ optional($post->category)->name }}" />
@foreach($keywordsArray as $kw)
  <meta property="article:tag" content="{{ $kw }}" />
@endforeach

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $post->title }}" />
<meta name="twitter:description" content="{{ Str::limit($metaDescClean, 200, '') }}" />
<meta name="twitter:image" content="{{ $ogImageSecure }}" />
<meta name="twitter:image:alt" content="{{ $post->title }}" />
@if(!empty($twitterHandle))
  <meta name="twitter:site" content="{{ $twitterHandle }}" />
@endif
@if (!empty($author?->twitter_handle))
  <meta name="twitter:creator" content="{{ Str::startsWith($author->twitter_handle, '@') ? $author->twitter_handle : '@'.$author->twitter_handle }}" />
@endif

<meta property="article:author" content="{{ $author->name ?? 'Unknown' }}" />
<link rel="author" href="{{ $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#' }}" />
@endsection

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
      <div class="col-12 col-lg-10 text-center">
        <!-- Category Badge -->
        @if($post->category)
          <div class="mb-3">
            <span class="badge bg-white text-primary px-3 py-2 rounded-pill">
              <i class="post-icon ti tabler-folder me-1"></i>{{ $post->category->name }}
            </span>
          </div>
        @endif

        <!-- Main Title -->
        <h1 class="display-5 text-white mb-3 fw-bold" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
          {{ $post->title }}
        </h1>

        <!-- Meta Information -->
        <div class="d-flex flex-wrap justify-content-center gap-3 text-white mb-3" style="font-size: 0.95rem;">
          <span class="d-flex align-items-center">
            <i class="post-icon ti tabler-user me-1"></i>
            {{ $author->name ?? __('Unknown') }}
          </span>
          <span class="d-flex align-items-center">
            <i class="post-icon ti tabler-calendar me-1"></i>
            {{ $post->created_at->format('d M Y') }}
          </span>
          <span class="d-flex align-items-center">
            <i class="post-icon ti tabler-eye me-1"></i>
            {{ number_format($post->views ?? 0) }} {{ __('views') }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Wave Shape Divider -->
  <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
      <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #f8f9fa;"></path>
    </svg>
  </div>
</section>

<!-- Breadcrumb -->
<div class="container px-4 mt-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-style2">
      <li class="breadcrumb-item">
        <a href="{{ route('home') }}">
          <i class="post-icon ti tabler-home me-1"></i>{{ __('Home') }}
        </a>
      </li>
      <li class="breadcrumb-item">
        <a href="{{ route('content.frontend.posts.index',['database' => $database]) }}">
          {{ __('Posts') }}
        </a>
      </li>
      @if($post->category)
        <li class="breadcrumb-item">
          <a href="{{ route('content.frontend.posts.index',['database' => $database, 'category' => $post->category->slug]) }}">
            {{ $post->category->name }}
          </a>
        </li>
      @endif
      <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($post->title, 50) }}</li>
    </ol>
  </nav>
</div>

<!-- Main Content -->
<section class="py-5" style="background-color: #f8f9fa;">
  <div class="container">
    <div class="row g-4">
      <!-- Main Article -->
      <div class="col-lg-8">
        <article class="card shadow-sm border-0 overflow-hidden">
          @php
            $processedContent = $post->content ?? '';
            $keywords = is_string($post->keywords) ? array_map('trim', explode(',', $post->keywords)) : [];
            usort($keywords, fn($a, $b) => strlen($b) - strlen($a));
            foreach ($keywords as $keyword) {
              if (!empty($keyword)) {
                $link = '<a href="' . route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword]) . '" class="keyword-link">' . e($keyword) . '</a>';
                $processedContent = preg_replace('/\b(' . preg_quote($keyword, '/') . ')\b/ui', $link, $processedContent, 1);
              }
            }
            $defaultImageUrl = match($database) {
              'sa' => asset('assets/img/front-pages/icons/articles_saudi_image.jpg'),
              'eg' => asset('assets/img/front-pages/icons/articles_egypt_image.jpg'),
              'ps' => asset('assets/img/front-pages/icons/articles_palestine_image.jpg'),
              default => asset('assets/img/front-pages/icons/articles_default_image.webp'),
            };
            $imageUrl = method_exists($post, 'imageUrl') ? $post->imageUrl() : (($post->image ?? null) ? asset('storage/' . ltrim($post->image, '/')) : $defaultImageUrl);
            $cleanBody = $processedContent;
            $cleanBody = preg_replace('/\s*&nbsp;\s*/u', ' ', $cleanBody);
            $titlePattern = '/^\s*<h[12][^>]*>\s*' . preg_quote(strip_tags($post->title ?? ''), '/') . '\s*<\/h[12]>\s*/iu';
            $cleanBody = preg_replace($titlePattern, '', $cleanBody, 1);
          @endphp

          <!-- Featured Image -->
          @if($post->image)
            <div class="position-relative" style="height: 400px; overflow: hidden;">
              @php
                $imgPath = ltrim(parse_url($imageUrl, PHP_URL_PATH) ?? '', '/');
                $src720 = $imgPath ? route('img.fit', ['size' => '720x400', 'path' => $imgPath]) : $imageUrl;
                $src960 = $imgPath ? route('img.fit', ['size' => '960x540', 'path' => $imgPath]) : $imageUrl;
                $src1200 = $imgPath ? route('img.fit', ['size' => '1200x630', 'path' => $imgPath]) : $imageUrl;
              @endphp
              <img
                src="{{ $src960 }}"
                srcset="{{ $src720 }} 720w, {{ $src960 }} 960w, {{ $src1200 }} 1200w"
                sizes="(max-width: 768px) 720px, (max-width: 1200px) 960px, 1200px"
                class="w-100 h-100"
                style="object-fit: cover;"
                alt="{{ e($post->title) }}"
                loading="eager">

              <!-- Image Overlay with Meta -->
              <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);">
                <div class="d-flex align-items-center gap-3 text-white">
                  @if($author)
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-2">
                        @if($author->profile_photo_url)
                          <img src="{{ $author->profile_photo_url }}" alt="{{ $author->name }}" class="rounded-circle">
                        @else
                          <span class="avatar-initial rounded-circle bg-primary">
                            {{ substr($author->name, 0, 1) }}
                          </span>
                        @endif
                      </div>
                      <span class="fw-medium">{{ $author->name }}</span>
                    </div>
                  @endif
                  <span class="text-white-50">•</span>
                  <span>{{ $post->created_at->diffForHumans() }}</span>
                </div>
              </div>
            </div>
          @endif

          <div class="card-body p-4 p-md-5">
            <!-- Article Content -->
            <div class="article-content fs-5 lh-lg text-dark">
              {!! $cleanBody !!}
            </div>

            <!-- Tags Section -->
            @if(!empty($keywords))
              <div class="mt-5 pt-4 border-top">
                <h5 class="mb-3 fw-bold">
                  <i class="post-icon ti tabler-tags me-2 text-primary"></i>{{ __('Related Tags') }}
                </h5>
                <div class="d-flex flex-wrap gap-2">
                  @foreach($keywords as $keyword)
                    @continue(empty($keyword))
                    <a href="{{ route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword]) }}"
                       class="badge bg-light text-primary px-3 py-2 text-decoration-none hover-shadow"
                       style="font-size: 0.875rem; font-weight: 500; transition: all 0.3s ease;">
                      #{{ $keyword }}
                    </a>
                  @endforeach
                </div>
              </div>
            @endif

            <!-- Attachments Section -->
            @if(!empty($post->attachments) && $post->attachments->count())
              <div class="mt-5 pt-4 border-top">
                <h5 class="mb-4 fw-bold">
                  <i class="post-icon ti tabler-paperclip me-2 text-primary"></i>{{ __('Attachments') }}
                  <span class="badge bg-primary ms-2">{{ $post->attachments->count() }}</span>
                </h5>
                <div class="row g-3">
                  @foreach($post->attachments as $file)
                    <div class="col-md-6">
                      <a href="{{ route('download.page', ['file' => $file->id]) }}"
                         target="_blank"
                         rel="noopener"
                         class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 border rounded-3 bg-light attachment-card"
                             style="transition: all 0.3s ease; min-height: 80px;">
                          <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-lg">
                              <span class="avatar-initial rounded-3 bg-label-primary">
                                <i class="post-icon ti tabler-file-download ti-lg"></i>
                              </span>
                            </div>
                          </div>
                          <div class="flex-grow-1 overflow-hidden">
                            <h6 class="mb-1 fw-semibold text-dark text-truncate"
                                title="{{ $file->file_name ?? basename($file->file_path) }}">
                              {{ Str::limit($file->file_name ?? basename($file->file_path), 40) }}
                            </h6>
                            @php
                              $size = (int) ($file->file_size ?? 0);
                              $sizeLabel = $size >= 1048576
                                ? number_format($size / 1048576, 2) . ' MB'
                                : number_format(max($size, 0) / 1024, 1) . ' KB';
                            @endphp
                            <small class="text-muted">
                              <i class="post-icon ti tabler-file-text me-1"></i>{{ $sizeLabel }}
                            </small>
                          </div>
                          <div class="flex-shrink-0 ms-2">
                            @php
                              $extLabel = strtoupper($file->file_type ?? pathinfo($file->file_path, PATHINFO_EXTENSION) ?: 'FILE');
                            @endphp
                            <span class="badge bg-primary">{{ $extLabel }}</span>
                          </div>
                        </div>
                      </a>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif

            <!-- Social Share Section -->
            <div class="mt-5 pt-4 border-top">
              <h5 class="mb-3 fw-bold">
                <i class="post-icon ti tabler-share me-2 text-primary"></i>{{ __('Share this post') }}
              </h5>
              <div class="d-flex flex-wrap gap-2">
                @php
                  $shareUrl = urlencode(url()->current());
                  $shareTitle = urlencode($post->title);
                @endphp
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-facebook">
                  <i class="post-icon ti tabler-brand-facebook me-1"></i>Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-twitter">
                  <i class="post-icon ti tabler-brand-twitter me-1"></i>Twitter
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-linkedin">
                  <i class="post-icon ti tabler-brand-linkedin me-1"></i>LinkedIn
                </a>
                <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="btn btn-whatsapp">
                  <i class="post-icon ti tabler-brand-whatsapp me-1"></i>WhatsApp
                </a>
              </div>
            </div>
          </div>
        </article>

        <!-- Comments Section -->
        <div class="card shadow-sm border-0 mt-4">
          <div class="card-body p-4 p-md-5">
            <h4 class="mb-4 fw-bold">
              <i class="post-icon ti tabler-messages me-2 text-primary"></i>{{ __('Comments') }}
              <span class="badge bg-label-primary ms-2">{{ $post->comments->count() }}</span>
            </h4>

            @if($post->comments->isNotEmpty())
              <div class="comments-list">
                @foreach($post->comments as $comment)
                  @php
                    $roleColor = $comment->user->hasRole('Admin') ? 'danger' :
                                 ($comment->user->hasRole('Supervisor') ? 'warning' : 'primary');
                    $avatarBg = 'bg-' . $roleColor;
                  @endphp
                  <div class="d-flex mb-4 pb-4 border-bottom comment-item" id="comment-{{ $comment->id }}">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar {{ $avatarBg }} rounded-circle">
                        <span class="avatar-initial text-white fw-bold">
                          {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                        </span>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h6 class="mb-0 text-{{ $roleColor }} fw-bold">
                            {{ $comment->user->name }}
                            @if($comment->user->hasRole('Admin'))
                              <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">{{ __('Admin') }}</span>
                            @elseif($comment->user->hasRole('Supervisor'))
                              <span class="badge bg-warning ms-1" style="font-size: 0.7rem;">{{ __('Supervisor') }}</span>
                            @endif
                          </h6>
                          <small class="text-muted">
                            <i class="post-icon ti tabler-clock me-1"></i>{{ $comment->created_at->diffForHumans() }}
                          </small>
                        </div>
                        @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->hasRole('Admin')))
                          <form action="{{ route('frontend.comments.destroy', ['database' => $database, 'id' => $comment->id]) }}"
                                method="POST"
                                onsubmit="return confirm('{{ __('Are you sure?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger p-1">
                              <i class="post-icon ti tabler-trash"></i>
                            </button>
                          </form>
                        @endif
                      </div>
                      <p class="mb-3 comment-body">{{ $comment->body }}</p>

                      <!-- Reactions -->
                      <div class="d-flex gap-2 flex-wrap">
                        <form action="{{ route('frontend.reactions.store', ['database' => $database]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="like">
                          <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="post-icon ti tabler-thumb-up me-1"></i>
                            {{ $comment->reactions->where('type', 'like')->count() }}
                          </button>
                        </form>
                        <form action="{{ route('frontend.reactions.store', ['database' => $database]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="love">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="post-icon ti tabler-heart me-1"></i>
                            {{ $comment->reactions->where('type', 'love')->count() }}
                          </button>
                        </form>
                        <form action="{{ route('frontend.reactions.store', ['database' => $database]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="laugh">
                          <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="post-icon ti tabler-mood-happy me-1"></i>
                            {{ $comment->reactions->where('type', 'laugh')->count() }}
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-5">
                <div class="avatar avatar-xl mb-3 mx-auto">
                  <span class="avatar-initial rounded-circle bg-label-primary">
                    <i class="post-icon ti tabler-message-x display-4"></i>
                  </span>
                </div>
                <h5 class="text-muted">{{ __('No comments yet') }}</h5>
                <p class="text-muted mb-0">{{ __('Be the first to comment!') }}</p>
              </div>
            @endif

            <!-- Add Comment Form -->
            @auth
              <div class="mt-4 pt-4 border-top">
                <h5 class="mb-3 fw-bold">
                  <i class="post-icon ti tabler-message-plus me-2"></i>{{ __('Add your comment') }}
                </h5>
                <form action="{{ route('frontend.comments.store', ['database' => $database]) }}" method="POST">
                  @csrf
                  <input type="hidden" name="commentable_id" value="{{ $post->id }}">
                  <input type="hidden" name="commentable_type" value="{{ get_class($post) }}">
                  <div class="mb-3">
                    <textarea class="form-control"
                              name="body"
                              rows="4"
                              required
                              placeholder="{{ __('Write your thoughtful comment here…') }}"
                              style="resize: vertical;"></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary">
                    <i class="post-icon ti tabler-send me-2"></i>{{ __('Submit Comment') }}
                  </button>
                </form>
              </div>
            @else
              <div class="mt-4 pt-4 border-top text-center">
                <p class="text-muted mb-3">{{ __('Please login to leave a comment') }}</p>
                <a href="{{ route('login') }}" class="btn btn-outline-primary">
                  <i class="post-icon ti tabler-login me-2"></i>{{ __('Login to comment') }}
                </a>
              </div>
            @endauth
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <!-- Related Posts -->
        @if($relatedNews->isNotEmpty())
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
              <h5 class="mb-0 text-white fw-bold">
                <i class="post-icon ti tabler-article me-2"></i>{{ __('Related Posts') }}
              </h5>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                @foreach($relatedNews as $relatedPost)
                  <a href="{{ route('content.frontend.posts.show', ['database' => $database, 'id' => $relatedPost->id]) }}"
                     class="list-group-item list-group-item-action border-0 p-3 related-post-item">
                    <div class="d-flex gap-3">
                      @if($relatedPost->image)
                        <div class="flex-shrink-0">
                          <img src="{{ asset('storage/' . $relatedPost->image) }}"
                               class="rounded"
                               alt="{{ $relatedPost->title }}"
                               style="width: 80px; height: 80px; object-fit: cover;"
                               onerror="this.src='{{ asset('assets/img/illustrations/default_news_image.jpg') }}'">
                        </div>
                      @endif
                      <div class="flex-grow-1">
                        <h6 class="mb-2 fw-semibold text-dark">
                          {{ Str::limit($relatedPost->title, 60) }}
                        </h6>
                        <div class="d-flex gap-2 text-muted small">
                          <span>
                            <i class="post-icon ti tabler-calendar me-1"></i>{{ $relatedPost->created_at->format('M d') }}
                          </span>
                          <span>•</span>
                          <span>
                            <i class="post-icon ti tabler-eye me-1"></i>{{ number_format($relatedPost->views ?? 0) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </a>
                @endforeach
              </div>
            </div>
          </div>
        @endif

        <!-- Author Card -->
        @if($author)
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4 text-center">
              <div class="avatar avatar-xl mb-3 mx-auto">
                @if($author->profile_photo_url)
                  <img src="{{ $author->profile_photo_url }}"
                       alt="{{ $author->name }}"
                       class="rounded-circle">
                @else
                  <span class="avatar-initial rounded-circle bg-primary fs-2">
                    {{ substr($author->name, 0, 1) }}
                  </span>
                @endif
              </div>
              <h5 class="mb-1 fw-bold">{{ $author->name }}</h5>
              @if($author->bio)
                <p class="text-muted small mb-3">{{ Str::limit($author->bio, 100) }}</p>
              @endif
              <a href="{{ route('front.members.show', ['database' => $database, 'id' => $author->id]) }}"
                 class="btn btn-sm btn-outline-primary">
                <i class="post-icon ti tabler-user me-1"></i>{{ __('View Profile') }}
              </a>
            </div>
          </div>
        @endif

        <!-- Ad Space -->
        @php
          $hasNewsSidebarAd = filled(config('settings.google_ads_desktop_news_2')) || filled(config('settings.google_ads_mobile_news_2'));
        @endphp
        @if($hasNewsSidebarAd)
          <div class="card shadow-sm border-0">
            <div class="card-body text-center p-0">
              <x-adsense.banner desktop-key="google_ads_desktop_news_2" mobile-key="google_ads_mobile_news_2" />
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

@push('page-style')
<style>
/* Article Content Styles */
.article-content {
  color: #2c3e50;
  line-height: 1.8;
}

.article-content h1, .article-content h2, .article-content h3,
.article-content h4, .article-content h5, .article-content h6 {
  font-weight: 700;
  margin-top: 1.5rem;
  margin-bottom: 1rem;
  color: #1a202c;
}

.article-content p {
  margin-bottom: 1.25rem;
}

.article-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 1.5rem 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.article-content ul, .article-content ol {
  margin-bottom: 1.25rem;
  padding-left: 2rem;
}

.article-content li {
  margin-bottom: 0.5rem;
}

.article-content blockquote {
  border-left: 4px solid #3498db;
  padding-left: 1.5rem;
  margin: 1.5rem 0;
  font-style: italic;
  color: #546e7a;
}

.article-content a {
  color: #3498db;
  text-decoration: none;
  transition: color 0.2s ease;
}

.article-content a:hover {
  color: #2980b9;
  text-decoration: underline;
}

.article-content code {
  background-color: #f5f5f5;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
  color: #e74c3c;
}

.article-content pre {
  background-color: #2c3e50;
  color: #ecf0f1;
  padding: 1rem;
  border-radius: 8px;
  overflow-x: auto;
  margin: 1.5rem 0;
}

.article-content pre code {
  background-color: transparent;
  color: inherit;
  padding: 0;
}

/* Keyword Links */
.keyword-link {
  color: #3498db;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
  border-bottom: 2px solid transparent;
}

.keyword-link:hover {
  color: #2980b9;
  border-bottom-color: #3498db;
}

/* Social Share Buttons */
.btn-facebook {
  background-color: #3b5998;
  color: white;
  border: none;
}

.btn-facebook:hover {
  background-color: #2d4373;
  color: white;
}

.btn-twitter {
  background-color: #1da1f2;
  color: white;
  border: none;
}

.btn-twitter:hover {
  background-color: #0c85d0;
  color: white;
}

.btn-linkedin {
  background-color: #0077b5;
  color: white;
  border: none;
}

.btn-linkedin:hover {
  background-color: #005582;
  color: white;
}

.btn-whatsapp {
  background-color: #25d366;
  color: white;
  border: none;
}

.btn-whatsapp:hover {
  background-color: #1da851;
  color: white;
}

/* Attachment Cards */
.attachment-card {
  cursor: pointer;
}

.attachment-card:hover {
  background-color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
  border-color: #3498db !important;
}

/* Related Posts */
.related-post-item {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f0f0f0 !important;
}

.related-post-item:hover {
  background-color: #f8f9fa;
  padding-left: 1.25rem !important;
}

.related-post-item:last-child {
  border-bottom: none !important;
}

/* Comment Styles */
.comment-item {
  transition: all 0.3s ease;
}

.comment-item:last-child {
  border-bottom: none !important;
  padding-bottom: 0 !important;
  margin-bottom: 0 !important;
}

.comment-body {
  white-space: pre-wrap;
  word-wrap: break-word;
  line-height: 1.6;
  color: #546e7a;
}

.comment-highlight {
  background-color: #fff7e6;
  border-radius: 8px;
  animation: highlightFade 2s ease;
}

@keyframes highlightFade {
  0% { background-color: #fff7e6; }
  100% { background-color: transparent; }
}

/* Badge Hover */
.badge.hover-shadow:hover {
  box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  .article-content {
    font-size: 1rem;
  }

  .display-5 {
    font-size: 1.75rem;
  }
}
</style>
@endpush

@push('page-script')
@php
  // Schema.org structured data
  $plainContent = trim(strip_tags($post->content ?? ''));
  $wordCount = $plainContent ? str_word_count($plainContent) : null;
  $keywordsArrayForSchema = is_string($post->keywords) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];
  $articleSection = optional($post->category)->name;
  $imageUrl = $post->image ? asset('storage/' . $post->image) : null;

  $articleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => request()->url(),
    ],
    'headline' => Str::limit($post->title ?? '', 110, ''),
    'url' => request()->url(),
    'name' => $post->title,
    'description' => $post->meta_description ?? Str::limit($plainContent, 160, ''),
    'articleBody' => $plainContent,
    'wordCount' => $wordCount,
    'keywords' => $keywordsArrayForSchema,
    'inLanguage' => 'ar',
    'datePublished' => optional($post->created_at)->toIso8601String(),
    'dateModified' => optional($post->updated_at)->toIso8601String(),
    'image' => $imageUrl ? [$imageUrl] : null,
    'articleSection' => $articleSection,
    'author' => [
      '@type' => 'Person',
      'name' => $author->name ?? 'Unknown',
      '@id' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#author',
      'url' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#',
    ],
    'publisher' => [
      '@type' => 'Organization',
      'name' => config('settings.site_name'),
      'logo' => [
        '@type' => 'ImageObject',
        'url' => asset('storage/' . config('settings.site_logo')),
      ],
    ],
  ];

  $breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
      [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => __('Home'),
        'item' => url('/'),
      ],
      [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => __('Posts'),
        'item' => route('content.frontend.posts.index', ['database' => $database]),
      ],
      [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $post->title,
        'item' => request()->url(),
      ],
    ],
  ];
@endphp

<script type="application/ld+json">{!! json_encode(array_filter($articleSchema, fn($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Smooth scroll to comment
  const hash = window.location.hash;
  if (hash && hash.startsWith('#comment-')) {
    const el = document.querySelector(hash);
    if (el) {
      setTimeout(() => {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('comment-highlight');
        setTimeout(() => el.classList.remove('comment-highlight'), 2500);
      }, 100);
    }
  }

  // Copy link functionality
  const copyButtons = document.querySelectorAll('.copy-link');
  copyButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="post-icon ti tabler-check me-1"></i>تم النسخ!';
        setTimeout(() => {
          this.innerHTML = originalText;
        }, 2000);
      });
    });
  });
});
</script>
@endpush

@endsection
