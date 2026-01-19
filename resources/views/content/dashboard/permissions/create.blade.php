@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Create New Permission'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="tabler-key icon-base ti icon-md me-2"></i> {{ __('Create New Permission') }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.permissions.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Permission Name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" placeholder="e.g. create-users"
                               value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">{{ __('Use lowercase with hyphens (e.g., edit-posts)') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Guard Name') }}</label>
                        <select class="form-select" name="guard_name">
                            @foreach(($validGuards ?? ['web']) as $guard)
                                <option value="{{ $guard }}" {{ old('guard_name', config('auth.defaults.guard', 'web')) === $guard ? 'selected' : '' }}>
                                    {{ $guard }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="tabler-check icon-base ti icon-md me-2"></i> {{ __('Save') }}
                        </button>
                        <a href="{{ route('dashboard.permissions.index') }}" class="btn btn-outline-secondary">
                            <i class="tabler-arrow-left icon-base ti icon-md me-2"></i> {{ __('Cancel') }}
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
