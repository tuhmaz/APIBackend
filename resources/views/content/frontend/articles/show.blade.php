@php
  $configData = Helper::appClasses();
  use Illuminate\Support\Str;

  $database = session('database', 'jo');

  $pageTitle = trim($article->title);

  // Ø®Ø±Ø§Ø¦Ø· Ù…Ø³Ø§Ø¹Ø¯Ø© Ù„Ù„ØºØ§Øª Ø§Ù„Ø´Ø¨ÙƒØ§Øª Ø§Ù„Ø§Ø¬ØªÙ…Ø§Ø¹ÙŠØ©
  $localeMap = ['sa' => 'ar_SA', 'eg' => 'ar_EG', 'ps' => 'ar_PS', 'jo' => 'ar_JO'];
  $ogLocale = $localeMap[$database] ?? 'ar_JO';

  // ØµÙˆØ±Ø© og Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠØ©
  $fallbackOgImage = asset('assets/img/front-pages/icons/articles_default_image.webp');
  $ogImage = $article->image_url ?: $fallbackOgImage;
  $ogImageSecure = preg_replace('#^http://#i', 'https://', $ogImage);

  // ØªÙ†Ø³ÙŠÙ‚ twitter:site ÙƒÙ€ @handle Ø­ØªÙ‰ Ù„Ùˆ ÙƒØ§Ù† Ù…Ø­ÙÙˆØ¸Ù‹Ø§ ÙƒØ±Ø§Ø¨Ø·
  $configuredTwitter = trim((string) config('settings.social_twitter'));
  $twitterHandle = $configuredTwitter;
  if (Str::startsWith($configuredTwitter, ['http://twitter.com/', 'https://twitter.com/'])) {
    $twitterHandle = '@' . ltrim(parse_url($configuredTwitter, PHP_URL_PATH), '/');
  }
  if ($twitterHandle && $twitterHandle[0] !== '@') {
    $twitterHandle = '@' . $twitterHandle;
  }
@endphp

@extends('layouts/layoutFront')

@section('title', $pageTitle)
@section('meta_title', $pageTitle . ' - ' . ($article->meta_title ?? config('settings.meta_title')))

@section('page-style')
  @vite('resources/assets/vendor/scss/pages/front-page-help-center.scss')
@endsection

@section('meta')
  <meta name="keywords" content="{{ implode(',', $keywordsList ?? []) }}">
  <meta name="description" content="{{ $article->meta_description }}">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="{{ url()->current() }}">

  <!-- Open Graph -->
  <meta property="og:title" content="{{ $article->title }}" />
  <meta property="og:description" content="{{ $article->meta_description }}" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:image" content="{{ $ogImage }}" />
  <meta property="og:image:secure_url" content="{{ $ogImageSecure }}" />
  <meta property="og:image:alt" content="{{ $article->title }}" />
  <meta property="og:image:width" content="800" />
  <meta property="og:image:height" content="600" />
  <meta property="og:locale" content="{{ $ogLocale }}" />
  <meta property="og:site_name" content="{{ config('settings.site_name', 'site_name') }}" />
  <meta property="article:published_time" content="{{ $article->created_at->toIso8601String() }}" />
  <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}" />

  @if ($author)
    {{-- Open Graph ÙŠÙØ¶Ù‘Ù„ Ø±Ø§Ø¨Ø· Ø¨Ø±ÙˆÙØ§ÙŠÙ„ Ø§Ù„Ù…Ø¤Ù„Ù --}}
    <meta property="article:author" content="{{ route('front.members.show', ['database' => $database, 'id' => $author->id]) }}" />
    <link rel="author" href="{{ route('front.members.show', ['database' => $database, 'id' => $author->id]) }}" />
  @else
    <meta property="article:author" content="{{ url('/') }}" />
  @endif

  <meta property="article:section" content="{{ $subject->subject_name }}" />
  {{-- ÙƒØ±Ø± article:tag Ù„ÙƒÙ„ ÙƒÙ„Ù…Ø© Ù…ÙØªØ§Ø­ÙŠØ© --}}
  @foreach($article->keywords as $kw)
    <meta property="article:tag" content="{{ $kw->keyword }}" />
  @endforeach

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $article->title }}" />
  <meta name="twitter:description" content="{{ $article->meta_description }}" />
  <meta name="twitter:image" content="{{ $ogImageSecure }}" />
  <meta name="twitter:image:alt" content="{{ $article->title }}" />
  @if(!empty($twitterHandle))
    <meta name="twitter:site" content="{{ $twitterHandle }}" />
  @endif
  @if (!empty($author?->twitter_handle))
    <meta name="twitter:creator" content="{{ Str::startsWith($author->twitter_handle, '@') ? $author->twitter_handle : '@'.$author->twitter_handle }}" />
  @endif
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
        <div class="animate__animated animate__fadeInDown">
          <h2 class="display-6 text-white mb-2" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $subject->subject_name }}</h2>
          <p class="text-center text-white px-4 mb-0" style="font-size: medium;">
            {{ $grade_level }} - {{ $subject->subject_name }} - {{ $semester->semester_name }}
          </p>
        </div>

        @guest
          <div class="d-flex justify-content-center gap-3 animate__animated animate__fadeInUp animate__delay-1s">
            <a href="{{ route('login') }}" class="btn btn-primary btn-lg" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);">
              <i class="article-icon ti tabler-user-plus me-2"></i>{{ __('Get Started') }}
            </a>
            <a href="#features" class="btn btn-outline-light btn-lg">
              <i class="article-icon ti tabler-info-circle me-2"></i>{{ __('Learn More') }}
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

<div class="container px-4 mt-4">
  <ol class="breadcrumb breadcrumb-style2" aria-label="breadcrumbs">
    <li class="breadcrumb-item">
      <a href="{{ route('home') }}">
        <i class="article-icon ti tabler-home-check"></i>{{ __('Home') }}
      </a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('frontend.lesson.index', ['database' => $database ?? session('database', 'default_database')]) }}">{{ __('Classes') }}</a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('frontend.lesson.show', ['database' => $database ?? session('database', 'default_database'),'id' => $subject->schoolClass->id]) }}">
        {{ $subject->schoolClass->grade_name }}
      </a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('frontend.subjects.show', ['database' => $database ?? session('database', 'default_database'),'subject' => $subject->id]) }}">
        {{ $subject->subject_name }}
      </a>
    </li>
    <li class="breadcrumb-item">
      <a href="{{ route('frontend.subject.articles', ['database' => $database ?? session('database', 'default_database'),'subject' => $subject->id, 'semester' => $semester->id, 'category' => $category]) }}">
        {{ __($category) }} - {{ $semester->semester_name }}
      </a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
  </ol>
  <div class="progress mt-2" aria-hidden="true">
    <div class="progress-bar" style="width: 100%;"></div>
  </div>
</div>

<section class="section-py bg-body first-section-pt" style="padding-top: 10px;">
  <div class="container mt-4 mb-4">
    
  </div>

  <div class="container">
    <div class="card px-3 mt-6">
      <div class="row">
        <div class="content-header text-center bg-primary py-3">
          <h2 class="text-white">{{ $article->title }}</h2>
        </div>

        <div class="content-body text-center mt-3">
          @php
            $file = $article->files->first();
            $fileType = $file?->file_type ?? 'default';
            $imagePath = match ($fileType) {
              'pdf' => asset('assets/img/icon/pdf-icon.png'),
              'doc', 'docx' => asset('assets/img/icon/word-icon.png'),
              'xls', 'xlsx' => asset('assets/img/icon/excel-icon.png'),
              default => asset('assets/img/icon/default-icon.png'),
            };
          @endphp

          @php
            $docImgPath = ltrim(parse_url($imagePath, PHP_URL_PATH) ?? '', '/');
            $doc100 = $docImgPath ? route('img.fit', ['size' => '100x100', 'path' => $docImgPath]) : $imagePath;
            $doc200 = $docImgPath ? route('img.fit', ['size' => '200x200', 'path' => $docImgPath]) : $imagePath;
          @endphp
          <img
            src="{{ $doc100 }}"
            srcset="{{ $doc100 }} 1x, {{ $doc200 }} 2x"
            sizes="100px"
            class="img-fluid document-icon mb-3 mt-3"
            alt="Document Icon"
            loading="lazy"
            decoding="async"
            width="100" height="100">

          <h3 class="mb-3">
            @switch($category)
              @case('plans')  {{ __('study_plans') }} @break
              @case('papers') {{ __('worksheets') }}  @break
              @case('tests')  {{ __('tests') }}       @break
              @case('books')  {{ __('school_books') }}@break
              @default        {{ __('articles') }}
            @endswitch
          </h3>

          <div class="divider divider-success">
            <div class="divider-text">{{ $subject->subject_name }} - {{ $semester->semester_name }}</div>
          </div>

          <div class="table-responsive mb-4">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th scope="row">{{ __('grade') }}</th>
                  <td>{{ $grade_level }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('semester') }}</th>
                  <td>{{ $semester->semester_name }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('subject') }}</th>
                  <td>{{ $subject->subject_name }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('content_type') }}</th>
                  <td>
                    @switch($category)
                      @case('plans')  {{ __('study_plans') }} @break
                      @case('papers') {{ __('worksheets') }}  @break
                      @case('tests')  {{ __('tests') }}       @break
                      @case('books')  {{ __('school_books') }}@break
                      @default        {{ __('articles') }}
                    @endswitch
                  </td>
                </tr>
                <tr>
                  <th scope="row">{{ __('last_updated') }}</th>
                  <td>{{ $article->updated_at->format('d M Y') }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('keywords') }}</th>
                  <td>{{ implode(' | ', $keywordsList ?? []) }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('visits') }}</th>
                  <td>{{ (int) $article->visit_count }}</td>
                </tr>
                <tr>
                  <th scope="row">{{ __('downloads') }}</th>
                  <td>{{ optional($file)->download_count ?? 0 }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="card mb-4 p-3">
            <div class="card-text">
              @php
                // ØµÙˆØ±Ø© Ø§ÙØªØ±Ø§Ø¶ÙŠØ© Ø­Ø³Ø¨ Ø§Ù„Ø¯ÙˆÙ„Ø©
                $defaultImageUrl = match($database) {
                  'sa' => asset('assets/img/front-pages/icons/articles_saudi_image.jpg'),
                  'eg' => asset('assets/img/front-pages/icons/articles_egypt_image.jpg'),
                  'ps' => asset('assets/img/front-pages/icons/articles_palestine_image.jpg'),
                  default => asset('assets/img/front-pages/icons/articles_default_image.webp'),
                };

                // Ø§Ø®ØªÙŽØ± ØµÙˆØ±Ø© Ø§Ù„Ù…Ù‚Ø§Ù„ (Ø¯Ø§Ù„Ø© model Ø¥Ù† ÙˆÙØ¬Ø¯Øª)
                $imageUrl = method_exists($article, 'imageUrl')
                  ? $article->imageUrl()
                  : ($article->image_url ?? $defaultImageUrl);
                $imageCaption = $article->image_caption ?? null;

                // Ù†ØµÙ‘ Ø¢Ù…Ù†/Ù…Ù†Ø¸Ù
                $cleanBody = property_exists($article, 'safe_body_html') && !empty($article->safe_body_html)
                  ? $article->safe_body_html
                  : ($article->content ?? '');
                // Ø¥Ø²Ø§Ù„Ø© &nbsp;
                $cleanBody = preg_replace('/\s*&nbsp;\s*/u', ' ', $cleanBody);
                // Ø¥Ø²Ø§Ù„Ø© Ø¹Ù†ÙˆØ§Ù† Ù…ÙƒØ±Ø± ÙƒÙ€ H1/H2 ÙÙŠ Ø£ÙˆÙ„ Ø§Ù„Ù…Ø­ØªÙˆÙ‰
                $titlePattern = '/^\s*<h[12][^>]*>\s*' . preg_quote(strip_tags($article->title), '/') . '\s*<\/h[12]>\s*/iu';
                $cleanBody = preg_replace($titlePattern, '', $cleanBody, 1);
              @endphp

              <style>
                .article-default-image{max-width:100%;height:auto;display:block;margin:0 auto;max-height:300px}
                @media (max-width:768px){.article-default-image{max-width:90%;max-height:200px}}
                .article-content img{max-width:100%;height:auto}
                /* Constrain article main image */
                .article-figure{max-width:900px;margin:0 auto}
                .article-hero-image{height:auto;max-height:480px}
                @media (max-width:768px){.article-hero-image{max-height:220px}}
              </style>

              <div class="article-content">
                <figure class="mb-4 article-figure text-center">
                  @php
                    $imgPath = ltrim(parse_url($imageUrl, PHP_URL_PATH) ?? '', '/');
                    $src220 = $imgPath ? route('img.fit', ['size' => '220x220', 'path' => $imgPath]) : $imageUrl;
                    $src360 = $imgPath ? route('img.fit', ['size' => '360x200', 'path' => $imgPath]) : $imageUrl;
                    $src720 = $imgPath ? route('img.fit', ['size' => '720x400', 'path' => $imgPath]) : $imageUrl;
                    $src960 = $imgPath ? route('img.fit', ['size' => '960x540', 'path' => $imgPath]) : $imageUrl;
                  @endphp
                  <img
                    src="{{ $src720 }}"
                    srcset="{{ $src220 }} 220w, {{ $src360 }} 360w, {{ $src720 }} 720w, {{ $src960 }} 960w"
                    sizes="(max-width: 576px) 220px, (max-width: 768px) 360px, (max-width: 992px) 720px, 960px"
                    class="img-fluid article-hero-image rounded"
                    alt="{{ e($article->title) }}"
                    decoding="async"
                    fetchpriority="high">
                  @if(!empty($imageCaption))
                    <figcaption class="text-muted small mt-2">{{ $imageCaption }}</figcaption>
                  @endif
                </figure>

                <div class="post-body">
                  {!! $cleanBody !!}
                </div>


                @foreach($article->keywords as $keyword)
                  @continue(empty($keyword->keyword))
                  <a href="{{ route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword->keyword]) }}">{{ $keyword->keyword }}</a>
                  @if(!$loop->last) <span class="keyword-separator"> | </span> @endif
                @endforeach
              </div>
            </div>

            <x-adsense.banner desktop-key="google_ads_desktop_article" mobile-key="google_ads_mobile_article" class="my-4" />
            {{-- Ø¹Ø±Ø¶ ÙˆØµÙ Ø§Ù„Ù…ÙŠØªØ§ ÙƒÙ†Øµ ÙÙ‚Ø· (Ø¨Ø¯ÙˆÙ† HTML) Ù„ØªØ­Ø§Ø´ÙŠ Ø§Ù„ØªØ¯Ø§Ø®Ù„ --}}
            <p class="mt-3 text-muted">{{ strip_tags($article->meta_description) }}</p>
          </div>

          @foreach ($article->files as $file)
            <div class="divider divider-danger">
              <div class="divider-text">
                <a href="{{ route('download.page', ['file' => $file->id]) }}" class="btn btn-outline-danger" target="_blank" rel="noopener noreferrer nofollow">
                  {{ __('download') }}
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <div class="social-share">
          @php
            $share = urlencode(request()->fullUrl());
            $title = urlencode($article->title);
          @endphp
          <a href="https://www.facebook.com/sharer/sharer.php?u={{ $share }}" target="_blank" rel="noopener noreferrer" class="btn btn-icon btn-outline-primary" aria-label="{{ __('Share on Facebook') }}" title="{{ __('Share on Facebook') }}">
            <i class="article-icon ti tabler-brand-facebook" aria-hidden="true"></i>
            <span class="visually-hidden">{{ __('Share on Facebook') }}</span>
          </a>
          <a href="https://twitter.com/intent/tweet?url={{ $share }}&text={{ $title }}" target="_blank" rel="noopener noreferrer" class="btn btn-icon btn-outline-info" aria-label="{{ __('Share on X (Twitter)') }}" title="{{ __('Share on X (Twitter)') }}">
            <i class="article-icon ti tabler-brand-twitter" aria-hidden="true"></i>
            <span class="visually-hidden">{{ __('Share on X (Twitter)') }}</span>
          </a>
          <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $share }}&title={{ $title }}" target="_blank" rel="noopener noreferrer" class="btn btn-icon btn-outline-primary" aria-label="{{ __('Share on LinkedIn') }}" title="{{ __('Share on LinkedIn') }}">
            <i class="article-icon ti tabler-brand-linkedin" aria-hidden="true"></i>
            <span class="visually-hidden">{{ __('Share on LinkedIn') }}</span>
          </a>
        </div>


        <div class="card mt-4">
          <div class="card-body">
            <h3>{{ __('Add a Comment') }}</h3>
            <form action="{{ route('frontend.comments.store', ['database' => $database ?? session('database')]) }}" method="POST">
              @csrf
              <input type="hidden" name="commentable_id" value="{{ $article->id }}">
              <input type="hidden" name="commentable_type" value="App\Models\Article">
              <div class="mb-3">
                <label for="comment-body" class="form-label">{{ __('Your Comment') }}</label>
                <textarea id="comment-body" class="form-control" name="body" rows="3" required aria-required="true" placeholder="{{ __('Write your comment hereâ€¦') }}"></textarea>
              </div>
              <button type="submit" class="btn btn-primary">{{ __('Add Comment') }}</button>
            </form>

            @foreach($article->comments as $comment)
              <div class="mt-4" id="comment-{{ $comment->id }}">
                @php
                  $roleColor = $comment->user->hasRole('Admin') ? 'text-danger' :
                               ($comment->user->hasRole('Supervisor') ? 'text-warning' : 'text-primary');
                  $dividerColor = $roleColor == 'text-danger' ? 'divider-danger' :
                                  ($roleColor == 'text-warning' ? 'divider-warning' : 'divider-primary');
                @endphp
                <div class="divider {{ $dividerColor }}">
                  <div class="divider-text {{ $roleColor }}">
                    {{ $comment->user->name }}
                  </div>
                </div>
                <p class="comment-body">{{ $comment->body }}</p>

                @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->hasRole('Admin')))
                  <div class="text-center mb-2">
                    <form action="{{ route('frontend.comments.destroy', ['database' => $database ?? session('database'), 'id' => $comment->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}');" class="d-inline-block">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="comment-icon ti tabler-trash"></i> {{ __('Delete') }}
                      </button>
                    </form>
                  </div>
                @endif

                <div class="reactions-inline-spacing d-flex justify-content-center">
                  <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline-block">
                    @csrf
                    <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                    <input type="hidden" name="type" value="like">
                    <button type="submit" class="btn btn-outline-info btn-sm">
                      <i class="article-icon ti tabler-thumb-up me-1"></i> {{ __('Like') }}
                      <span class="badge bg-white text-info ms-1">{{ $comment->reactions->where('type', 'like')->count() }}</span>
                    </button>
                  </form>

                  <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline-block">
                    @csrf
                    <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                    <input type="hidden" name="type" value="love">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                      <i class="article-icon ti tabler-heart me-1"></i> {{ __('Love') }}
                      <span class="badge bg-white text-danger ms-1">{{ $comment->reactions->where('type', 'love')->count() }}</span>
                    </button>
                  </form>

                  <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline-block">
                    @csrf
                    <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                    <input type="hidden" name="type" value="laugh">
                    <button type="submit" class="btn btn-outline-warning btn-sm">
                      <i class="article-icon ti tabler-mood-happy me-1"></i> {{ __('Laugh') }}
                      <span class="badge bg-white text-warning ms-1">{{ $comment->reactions->where('type', 'laugh')->count() }}</span>
                    </button>
                  </form>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div> <!-- /.row -->
    </div>   <!-- /.card -->
  </div>     <!-- /.container -->
</section>

@php
  // ØµÙˆØ±Ø© Ù„Ù„Ù€ JSON-LD (Ø£ÙˆÙ„ ØµÙˆØ±Ø© Ø¯Ø§Ø®Ù„ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø£Ùˆ Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠØ© Ø­Ø³Ø¨ Ø§Ù„Ø¯ÙˆÙ„Ø©)
  $country = session('database', 'jo');
  $defaultImageUrl = match($country) {
    'sa' => asset('assets/img/front-pages/icons/articles_saudi_image.jpg'),
    'eg' => asset('assets/img/front-pages/icons/articles_egypt_image.jpg'),
    'ps' => asset('assets/img/front-pages/icons/articles_palestine_image.jpg'),
    default => asset('assets/img/front-pages/icons/articles_default_image.webp'),
  };

  if (!function_exists('getFirstImageFromContent')) {
    function getFirstImageFromContent($content, $defaultImageUrl) {
      preg_match('/<img[^>]+src="([^">]+)"/i', (string) $content, $m);
      return $m[1] ?? $defaultImageUrl;
    }
  }
  $firstImageUrl = getFirstImageFromContent($article->content, $defaultImageUrl);

  // Ø´Ø¹Ø§Ø± Ø§Ù„Ù†Ø§Ø´Ø± Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠ Ù„Ùˆ ØºØ§Ø¨ Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯
  $publisherLogo = config('settings.site_logo')
    ? asset('storage/' . ltrim(config('settings.site_logo'), '/'))
    : asset('assets/img/front-pages/brand/logo-512.png');

  $articleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'inLanguage' => 'ar',
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => url()->current(),
    ],
    'headline' => $article->title,
    'name' => $article->title,
    'description' => $article->meta_description,
    'articleBody' => \Illuminate\Support\Str::limit(strip_tags($article->content), 300),
    'author' => [
      '@type' => 'Person',
      'name' => $author?->name ?? 'Anonymous',
      '@id' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#author',
      'url' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#',
    ],
    'datePublished' => $article->created_at->toIso8601String(),
    'dateModified' => $article->updated_at->toIso8601String(),
    'publisher' => [
    '@type' => 'Organization',
    '@id'   => url('/#organization'),
    'name'  => config('settings.site_name', 'Ù…ÙˆÙ‚Ø¹ Ø§Ù„Ø£ÙŠÙ…Ø§Ù†'),
    'url'   => url('/'),
    'logo'  => [
      '@type'  => 'ImageObject',
      'url'    => $publisherLogo,   // Ø§Ø³ØªØ®Ø¯Ù… Ù†Ø·Ø§Ù‚ production Ù…Ø¹ https
      'width'  => 512,
      'height' => 512,
      ],
    ],
    'image' => [
      '@type' => 'ImageObject',
      'url' => $firstImageUrl,
      'width' => 800,
      'height' => 600,
    ],
    'articleSection' => $subject->subject_name,
    'keywords' => $keywordsList ?? [],
'wordCount' => $wordCount ?? null,
'isAccessibleForFree' => true,
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
        'name' => __('Classes'),
        'item' => route('frontend.lesson.index', ['database' => $database ?? session('database', 'default_database')]),
      ],
      [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $subject->subject_name,
        'item' => route('frontend.subjects.show', ['database' => $database ?? session('database', 'default_database'), 'subject' => $subject->id]),
      ],
      [
        '@type' => 'ListItem',
        'position' => 4,
        'name' => $article->title,
        'item' => url()->current(),
      ],
    ],
  ];
@endphp
<script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script>
  (function() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#comment-')) {
      const el = document.querySelector(hash);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('comment-highlight');
        setTimeout(() => el.classList.remove('comment-highlight'), 2500);
      }
    }
  })();
</script>
<style>
  .comment-highlight { background: #fff7e6; transition: background 0.5s ease; border-radius: 6px; }
  .comment-body { white-space: pre-wrap; }
</style>
@endsection


