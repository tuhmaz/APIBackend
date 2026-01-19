@extends('layouts.contentNavbarLayout')

@section('title', 'التعليقات')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0">جميع التعليقات</h4>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('dashboard.comments.index') }}" class="row g-2">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control" placeholder="ابحث في نص التعليق" value="{{ $search ?? '' }}">
        </div>
        <div class="col-md-3">
          <select name="type" class="form-select">
            <option value="">الكل (مقالات + أخبار)</option>
            <option value="article" @selected(($type ?? '')==='article')>المقالات فقط</option>
            <option value="news" @selected(($type ?? '')==='news')>الأخبار فقط</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="per_page" class="form-select">
            @foreach([10,20,50,100] as $pp)
              <option value="{{ $pp }}" @selected(request('per_page', 20)==$pp)>{{ $pp }} / صفحة</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 text-end">
          <button class="btn btn-primary" type="submit">
            <i class="con-icon ti tabler-search me-1"></i>
            بحث
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>النص</th>
            <th>النوع</th>
            <th>العنصر</th>
            <th>المستخدم</th>
            <th>قاعدة البيانات</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          @forelse($comments as $comment)
            <tr id="comment-{{ $comment->id }}">
              <td>{{ $comment->id }}</td>
              <td style="max-width: 420px">
                <div class="text-wrap" style="white-space: normal;">
                  {{ Str::limit($comment->body, 160) }}
                </div>
              </td>
              <td>
                @php
                  $isArticle = $comment->commentable_type === \App\Models\Article::class;
                  $isNews = in_array($comment->commentable_type, [\App\Models\News::class, \App\Models\Post::class]);
                  $typeLabel = $isArticle ? 'مقال' : ($isNews ? 'خبر' : class_basename($comment->commentable_type));
                @endphp
                <span class="badge bg-label-{{ $isArticle ? 'info' : 'warning' }}">
                  {{ $typeLabel }}
                </span>
              </td>
              <td>
                @if($comment->commentable)
                  @if($isArticle)
                    <a href="{{ route('frontend.articles.show', ['database' => $comment->database, 'article' => $comment->commentable_id]) }}" target="_blank">
                      عرض
                    </a>
                  @elseif($isNews)
                    <a href="{{ route('content.frontend.posts.show', ['database' => $comment->database, 'id' => $comment->commentable_id]) }}" target="_blank">
                      عرض
                    </a>
                  @else
                    —
                  @endif
                @else
                  <span class="text-muted">غير متاح</span>
                @endif
              </td>
              <td>
                @if($comment->user)
                  <span class="d-block">{{ $comment->user->name }}</span>
                  <small class="text-muted">ID: {{ $comment->user->id }}</small>
                @else
                  <span class="text-muted">مستخدم غير معروف</span>
                @endif
              </td>
              <td><code>{{ $comment->database }}</code></td>
              <td>
                <span title="{{ $comment->created_at }}">{{ $comment->created_at->diffForHumans() }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-5">لا توجد تعليقات</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($comments->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
        <div class="small text-muted mb-2 mb-md-0">
          عرض {{ $comments->firstItem() }} - {{ $comments->lastItem() }} من أصل {{ $comments->total() }}
        </div>
        {{ $comments->onEachSide(1)->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
