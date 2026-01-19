@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Edit Permission'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="tabler-edit icon-base ti icon-md me-2"></i> {{ __('Edit Permission') }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.permissions.update', $permission) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Permission Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" placeholder="e.g. edit-users"
                               value="{{ old('name', $permission->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('Use lowercase with hyphens (e.g., edit-posts)') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Guard Name') }}</label>
                        <select class="form-select @error('guard_name') is-invalid @enderror" name="guard_name">
                            @foreach(($validGuards ?? ['web']) as $guard)
                                <option value="{{ $guard }}" {{ old('guard_name', $permission->guard_name) === $guard ? 'selected' : '' }}>
                                    {{ $guard }}
                                </option>
                            @endforeach
                        </select>
                        @error('guard_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="tabler-check icon-base ti icon-md me-2"></i> {{ __('Update') }}
                        </button>
                        <a href="{{ route('dashboard.permissions.index') }}" class="btn btn-outline-secondary">
                            <i class="tabler-arrow-left icon-base ti icon-md me-2"></i> {{ __('Back') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show success message if exists
    @if(session('success'))
    Swal.fire({
        icon: 'success',
        title: '{{ __("Success") }}',
        text: '{{ session("success") }}',
        timer: 3000,
        showConfirmButton: false
    });
    @endif
</script>
@endpush
