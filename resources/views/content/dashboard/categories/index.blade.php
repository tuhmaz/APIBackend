@extends('layouts.contentNavbarLayout')

@section('title', __('Categories Management'))

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <h5 class="mb-0">{{ __('Categories') }}</h5>
          <select class="form-select country-select" onchange="window.location.href='{{ route('dashboard.categories.index') }}?country=' + this.value">
            @foreach($countries as $code => $name)
              <option value="{{ $code }}" {{ $country == $code ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
        </div>
        <a href="{{ route('dashboard.categories.create', ['country' => $country]) }}" class="btn btn-primary">
          <i class="category-icon ti tabler-plus me-1"></i>{{ __('Add New Category') }}
        </a>
      </div>
      <div class="card-body">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Slug') }}</th>
                <th>{{ __('Depth') }}</th>
                <th>{{ __('News Count') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @php
                // Group categories by parent_id to build hierarchy
                $grouped = $categories->groupBy('parent_id');
                $row = 0;
                $parents = $grouped->get(null, collect());
              @endphp

              @forelse($parents as $parent)
                @php $row++; @endphp
                <tr>
                  <td>{{ $row }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      @php
                        $pIcon = $parent->icon ?? '';
                        $pIsPath = $pIcon && (\Illuminate\Support\Str::contains($pIcon, ['/', '\\']) || preg_match('/\.(png|jpe?g|gif|svg|webp)$/i', $pIcon));
                      @endphp
                      @if(!empty($pIcon) && $pIsPath)
                        <img src="{{ asset('storage/' . ltrim($pIcon, '/')) }}" alt="{{ $parent->name }}" style="height:24px;width:24px;object-fit:cover;border-radius:4px" class="me-2">
                      @elseif(!empty($pIcon))
                        <i class="{{ $pIcon }} me-2"></i>
                      @endif
                      <strong>{{ $parent->name }}</strong>
                      <span class="badge bg-label-secondary ms-2">{{ __('Parent') }}</span>
                    </div>
                  </td>
                  <td>{{ $parent->slug }}</td>
                  <td><span class="badge bg-label-secondary">{{ (int) ($parent->depth ?? 0) }}</span></td>
                  <td>
                    <span class="badge bg-label-info">
                      {{ $parent->news_count }} {{ __('News') }}
                    </span>
                  </td>
                  <td>
                    <div class="form-check form-switch">
                      <input
                        type="checkbox"
                        class="form-check-input toggle-status"
                        data-id="{{ $parent->id }}"
                        data-url="{{ route('dashboard.categories.toggle-status', ['category' => $parent->id, 'country' => $country]) }}"
                        {{ $parent->is_active ? 'checked' : '' }}
                      >
                    </div>
                  </td>
                  <td>
                    <div class="dropdown">
                      <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="category-icon ti tabler-dots-vertical"></i>
                      </button>
                      <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('dashboard.categories.edit', ['category' => $parent->id, 'country' => $country]) }}">
                          <i class="category-icon ti tabler-pencil me-1"></i>
                          {{ __('Edit') }}
                        </a>
                        <form action="{{ route('dashboard.categories.destroy', ['category' => $parent->id, 'country' => $country]) }}"
                              method="POST"
                              class="d-inline delete-form"
                              data-name="{{ $parent->name }}">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item">
                            <i class="category-icon ti tabler-trash me-1"></i>
                            {{ __('Delete') }}
                          </button>
                        </form>
                      </div>
                    </div>
                  </td>
                </tr>

                @php $children = $grouped->get($parent->id, collect()); @endphp
                @foreach($children as $child)
                  @php $row++; @endphp
                  <tr>
                    <td>{{ $row }}</td>
                    <td>
                      <div class="d-flex align-items-center" style="padding-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}: 24px;">
                        @php
                          $iconValue = $child->icon ?? '';
                          $isPath = $iconValue && (\Illuminate\Support\Str::contains($iconValue, ['/', '\\']) || preg_match('/\.(png|jpe?g|gif|svg|webp)$/i', $iconValue));
                        @endphp
                        @if(!empty($iconValue) && $isPath)
                          <img src="{{ asset('storage/' . ltrim($iconValue, '/')) }}" alt="{{ $child->name }}" style="height:20px;width:20px;object-fit:cover;border-radius:4px" class="me-2">
                        @elseif(!empty($iconValue))
                          <i class="{{ $iconValue }} me-2"></i>
                        @endif
                        <span>{{ $child->name }}</span>
                        <small class="text-muted ms-2">— {{ __('Child of') }} {{ $parent->name }}</small>
                      </div>
                    </td>
                    <td>{{ $child->slug }}</td>
                    <td><span class="badge bg-label-secondary">{{ (int) ($child->depth ?? (($parent->depth ?? 0) + 1)) }}</span></td>
                    <td>
                      <span class="badge bg-label-info">
                        {{ $child->news_count }} {{ __('News') }}
                      </span>
                    </td>
                    <td>
                      <div class="form-check form-switch">
                        <input
                          type="checkbox"
                          class="form-check-input toggle-status"
                          data-id="{{ $child->id }}"
                          data-url="{{ route('dashboard.categories.toggle-status', ['category' => $child->id, 'country' => $country]) }}"
                          {{ $child->is_active ? 'checked' : '' }}
                        >
                      </div>
                    </td>
                    <td>
                      <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                          <i class="category-icon ti tabler-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu">
                          <a class="dropdown-item" href="{{ route('dashboard.categories.edit', ['category' => $child->id, 'country' => $country]) }}">
                            <i class="category-icon ti tabler-pencil me-1"></i>
                            {{ __('Edit') }}
                          </a>
                          <form action="{{ route('dashboard.categories.destroy', ['category' => $child->id, 'country' => $country]) }}"
                                method="POST"
                                class="d-inline delete-form"
                                data-name="{{ $child->name }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item">
                              <i class="category-icon ti tabler-trash me-1"></i>
                              {{ __('Delete') }}
                            </button>
                          </form>
                        </div>
                      </div>
                    </td>
                  </tr>
                @endforeach
              @empty
                <tr>
                  <td colspan="6" class="text-center">{{ __('No categories found') }}</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('vendor-script')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endpush

@push('page-script')
@vite(['resources/assets/vendor/js/categories.js'])
@endpush
