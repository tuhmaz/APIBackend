@extends('layouts.contentNavbarLayout')

@section('title', __('File Details'))

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('File Details') }}</h5>
        <div>
          <a href="{{ route('dashboard.files.download', ['file' => $file->id, 'country' => $country]) }}"
             class="btn btn-primary">
            <i class="file-icon ti tabler-download me-1"></i>{{ __('Download File') }}
          </a>
          <a href="{{ route('dashboard.files.index', ['country' => $country]) }}"
             class="btn btn-secondary">
            <i class="file-icon ti tabler-arrow-left me-1"></i>{{ __('Back to List') }}
          </a>
        </div>
      </div>

      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-solid-primary d-flex align-items-center mb-4" role="alert">
            <span class="alert-icon rounded">
              <i class="icon-base ti tabler-check icon-md"></i>
            </span>
            <span class="ms-2">{{ session('success') }}</span>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-solid-danger d-flex align-items-center mb-4" role="alert">
            <span class="alert-icon rounded">
              <i class="icon-base ti tabler-alert-triangle icon-md"></i>
            </span>
            <span class="ms-2">{{ session('error') }}</span>
          </div>
        @endif
        <div class="row">
          <!-- معلومات الملف -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">{{ __('File Information') }}</h6>
                <div class="mb-3">
                  <div class="d-flex align-items-center mb-2">
                    @php
                      $iconClass = match($file->file_type) {
                        'pdf' => 'ti tabler-file-text text-danger',
                        'doc', 'docx' => 'ti tabler-file-description text-primary',
                        'xls', 'xlsx' => 'ti tabler-file-spreadsheet text-success',
                        'jpg', 'jpeg', 'png', 'gif' => 'ti tabler-photo text-info',
                        default => 'ti tabler-file text-secondary'
                      };
                    @endphp
                    <i class="file-icon ti {{ $iconClass }} me-2" style="font-size: 2rem;"></i>
                    <h5 class="mb-0">{{ $file->file_Name }}</h5>
                  </div>
                  <span class="badge bg-label-{{ match($file->file_category) {
                    'plans' => 'success',
                    'papers' => 'info',
                    'tests' => 'warning',
                    'books' => 'primary',
                    'records' => 'secondary',
                    default => 'secondary'
                  } }}">
                    {{ match($file->file_category) {
                      'plans' => __('Plans'),
                      'papers' => __('Papers'),
                      'tests' => __('Tests'),
                      'books' => __('Books'),
                      'records' => __('Records'),
                      default => __('Other')
                    } }}
                  </span>
                </div>

                <dl class="row mb-0">
                  <dt class="col-sm-4">{{ __('File Type') }}</dt>
                  <dd class="col-sm-8">{{ strtoupper($file->file_type) }}</dd>

                  <dt class="col-sm-4">{{ __('Upload Date') }}</dt>
                  <dd class="col-sm-8">{{ $file->created_at->format('Y-m-d H:i') }}</dd>

                  <dt class="col-sm-4">{{ __('Last Update') }}</dt>
                  <dd class="col-sm-8">{{ $file->updated_at->format('Y-m-d H:i') }}</dd>
                </dl>
              </div>
            </div>
          </div>

          <!-- معلومات المحتوى المرتبط (مقال/منشور) -->
          <div class="col-md-6">
            <div class="card bg-light border-0 h-100">
              <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">{{ __('Related Content') }}</h6>
                @if($file->article)
                  <h5 class="mb-3">
                    <span class="me-2">{{ $file->article->title }}</span>
                    <small class="badge bg-label-primary">{{ __('Article') }}</small>
                  </h5>
                  <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Class') }}</dt>
                    <dd class="col-sm-8">{{ $file->article->schoolClass->grade_name ?? __('N/A') }}</dd>

                    <dt class="col-sm-4">{{ __('Subject') }}</dt>
                    <dd class="col-sm-8">{{ $file->article->subject->subject_name ?? __('N/A') }}</dd>

                    <dt class="col-sm-4">{{ __('Semester') }}</dt>
                    <dd class="col-sm-8">{{ $file->article->semester->semester_name ?? __('N/A') }}</dd>
                  </dl>

                  <div class="mt-3">
                    <a href="{{ route('dashboard.articles.show', ['article' => $file->article->id, 'country' => $country]) }}"
                       class="btn btn-outline-primary btn-sm">
                      <i class="file-icon ti tabler-article me-1"></i>{{ __('View Article') }}
                    </a>
                  </div>
                @elseif($file->post)
                  <h5 class="mb-3">
                    <span class="me-2">{{ $file->post->title }}</span>
                    <small class="badge bg-label-info">{{ __('Post') }}</small>
                  </h5>
                  <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Category') }}</dt>
                    <dd class="col-sm-8">{{ $file->post->category->name ?? __('N/A') }}</dd>

                    <dt class="col-sm-4">{{ __('Author') }}</dt>
                    <dd class="col-sm-8">{{ $file->post->author->name ?? __('N/A') }}</dd>
                  </dl>

                  <div class="mt-3">
                    <a href="{{ route('dashboard.posts.edit', ['post' => $file->post->id, 'country' => $country]) }}"
                       class="btn btn-outline-info btn-sm">
                      <i class="file-icon ti tabler-news me-1"></i>{{ __('Edit Post') }}
                    </a>
                  </div>
                @else
                  <div class="text-center py-4">
                    <i class="file-icon ti tabler-article-off mb-2" style="font-size: 3rem;"></i>
                    <p class="mb-0">{{ __('No related content for this file') }}</p>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>

        <!-- معاينة الملف -->
        @if(in_array($file->file_type, ['jpg', 'jpeg', 'png', 'gif', 'pdf']))
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h6 class="mb-0">{{ __('File Preview') }}</h6>
              </div>
              <div class="card-body text-center">
                @php
                  $fileUrl = Storage::url($file->file_path);
                @endphp
                @if(in_array($file->file_type, ['jpg', 'jpeg', 'png', 'gif']))
                  <img src="{{ $fileUrl }}"
                       class="img-fluid"
                       alt="{{ $file->file_Name }}"
                       style="max-height: 600px; width: auto;">
                @elseif($file->file_type === 'pdf')
                  <div class="ratio ratio-16x9" style="height: 600px;">
                    <iframe src="{{ $fileUrl }}"
                            type="application/pdf"
                            width="100%"
                            height="100%"
                            class="rounded">
                      <p>{{ __('Your browser does not support PDF preview.') }}
                         <a href="{{ route('dashboard.files.download', ['file' => $file->id, 'country' => $country]) }}">
                           {{ __('Click here to download') }}
                         </a>
                      </p>
                    </iframe>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
