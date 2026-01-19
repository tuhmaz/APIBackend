@php
use Illuminate\Support\Str;
@endphp

@extends('layouts/contentNavbarLayout')

@section('title', __('Posts Management'))

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@push('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endpush

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="card-title mb-0">{{ __('Posts Management') }}</h5>
          <small class="text-muted">{{ __('Manage your posts') }}</small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <div class="country-selector">
            <select class="form-select form-select-sm" style="min-width: 150px;"
              onchange="window.location.href='{{ route('dashboard.posts.index') }}?country=' + this.value">
              <option value="1" {{ $currentCountry == '1' ? 'selected' : '' }}>Jordan</option>
              <option value="2" {{ $currentCountry == '2' ? 'selected' : '' }}>Saudi Arabia</option>
              <option value="3" {{ $currentCountry == '3' ? 'selected' : '' }}>Egypt</option>
              <option value="4" {{ $currentCountry == '4' ? 'selected' : '' }}>Palestine</option>
            </select>
          </div>
          <a href="{{ route('dashboard.posts.create', ['country' => $currentCountry]) }}" class="btn btn-primary">
            <i class="post-icon ti tabler-plus me-1"></i>{{ __('Add Post') }}
          </a>
        </div>
      </div>

      <div class="card-body">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-3" role="alert">
          <div class="d-flex gap-2 align-items-center">
            <i class="post-icon ti tabler-check"></i>
            {{ session('success') }}
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-3" role="alert">
          <div class="d-flex gap-2 align-items-center">
            <i class="post-icon ti tabler-alert-triangle"></i>
            {{ session('error') }}
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="table-responsive">
          <table class="datatables-posts table table-hover border-top">
            <thead>
              <tr>
                <th width="80">{{ __('Image') }}</th>
                <th>{{ __('Title') }}</th>
                <th>{{ __('Category') }}</th>
                <th width="100">{{ __('Status') }}</th>
                <th width="100">{{ __('Featured') }}</th>
                <th width="80">{{ __('Views') }}</th>
                <th width="150">{{ __('Created At') }}</th>
                <th width="120">{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse($posts as $item)
              <tr>
                <td>
                  <div class="avatar">
                    <img src="{{ $item->image_url }}"
                      alt="{{ $item->title }}"
                      class="rounded"
                      onerror="this.src='{{ asset('assets/img/illustrations/default_news_image.jpg') }}'">
                  </div>
                </td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="fw-semibold">{{ Str::limit($item->title, 50) }}</span>
                  </div>
                </td>
                <td>
                  <span class="badge bg-label-primary">{{ $item->category->name }}</span>
                </td>
                <td>
                  <form action="{{ route('dashboard.posts.toggle-status', ['post' => $item->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-check form-switch">
                      <input type="checkbox" class="form-check-input" onchange="this.form.submit()" {{ $item->is_active ? 'checked' : '' }}>
                      <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $item->is_active ? __('Active') : __('Inactive') }}</span>
                    </div>
                  </form>
                </td>
                <td>
                  <form action="{{ route('dashboard.posts.toggle-featured', ['post' => $item->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-check form-switch">
                      <input type="checkbox" class="form-check-input" onchange="this.form.submit()" {{ $item->is_featured ? 'checked' : '' }}>
                      <span class="badge {{ $item->is_featured ? 'bg-warning' : 'bg-secondary' }}">{{ $item->is_featured ? __('Featured') : __('Normal') }}</span>
                    </div>
                  </form>
                </td>
                <td>
                  <span class="badge bg-label-info">{{ number_format($item->views) }}</span>
                </td>
                <td>
                  <span class="text-muted"><i class="post-icon ti tabler-calendar me-1"></i>{{ $item->created_at->format('Y-m-d H:i') }}</span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="{{ route('dashboard.posts.edit', ['post' => $item->id, 'country' => $currentCountry]) }}" class="btn btn-icon btn-label-primary btn-sm">
                      <i class="post-icon ti tabler-edit"></i>
                    </a>
                    <form action="{{ route('dashboard.posts.destroy', ['post' => $item->id]) }}" method="POST" onsubmit="return confirm('{{ __("Are you sure?") }}')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-icon btn-label-danger btn-sm">
                        <i class="post-icon ti tabler-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center py-4">
                  <div class="text-center">
                    <i class="post-icon ti tabler-news text-secondary mb-2" style="font-size: 3rem;"></i>
                    <p class="mb-0">{{ __('No posts found') }}</p>
                    <small class="text-muted">{{ __('Start by adding your first post') }}</small>
                  </div>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="pagination pagination-outline-secondary">
          {{ $posts->links('components.pagination.custom') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
