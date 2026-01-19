@extends('layouts.contentNavbarLayout')

@section('title', __('Create Category'))

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Create New Category') }}</h5>
        <a href="{{ route('dashboard.categories.index', ['country' => $country]) }}" class="btn btn-secondary">
          <i class="category-icon ti tabler-arrow-left me-1"></i>{{ __('Back to Categories') }}
        </a>
      </div>
      <div class="card-body">
        @if(session('error'))
          <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <form action="{{ route('dashboard.categories.store') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="icon_image">{{ __('Category Icon (Avatar)') }}</label>
              <input type="file" class="form-control @error('icon_image') is-invalid @enderror" id="icon_image" name="icon_image" accept="image/*">
              @error('icon_image')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">{{ __('Optional small avatar shown in lists.') }}</small>
            </div>
            <div class="col-12">
              <label class="form-label" for="icon">{{ __('Icon class or image path') }}</label>
              <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon" value="{{ old('icon') }}" placeholder="page-icon ti tabler-news or uploads/icons/news.png">
              @error('icon')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">{{ __('You can enter a CSS class (e.g., page-icon ti tabler-news) or a relative image path stored in storage.') }}</small>
            </div>
            <div class="col-12">
              <label class="form-label" for="image">{{ __('Main image') }}</label>
              <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
              @error('image')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="text-muted">{{ __('Large image used in hero/headers for this category.') }}</small>
            </div>
            <div class="col-12">
              <label class="form-label" for="country">{{ __('Country') }} <span class="text-danger">*</span></label>
              <select class="form-select @error('country') is-invalid @enderror"
                      id="country"
                      name="country"
                      required>
                @foreach($countries as $code => $name)
                  <option value="{{ $code }}" {{ old('country', $country) == $code ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
              @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="name">{{ __('Category Name') }} <span class="text-danger">*</span></label>
              <input type="text"
                     class="form-control @error('name') is-invalid @enderror"
                     id="name"
                     name="name"
                     value="{{ old('name') }}"
                     required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="parent_id">{{ __('Parent Category') }}</label>
              <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                <option value="">— {{ __('None') }} —</option>
                @isset($parentCategories)
                  @foreach($parentCategories as $pc)
                    <option value="{{ $pc->id }}" {{ old('parent_id') == $pc->id ? 'selected' : '' }}>
                      {{ $pc->name }}
                    </option>
                  @endforeach
                @endisset
              </select>
              @error('parent_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="depth">{{ __('Depth') }}</label>
              <input type="number" id="depth" class="form-control" value="0" readonly>
              <small class="text-muted">{{ __('Depth is calculated automatically based on the selected parent (0 = root).') }}</small>
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       id="is_active"
                       name="is_active"
                       value="1"
                       {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
              </div>
            </div>

            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary">
                <i class="category-icon ti tabler-device-floppy me-1"></i>{{ __('Create Category') }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
