@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Create New Role'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">
                    <i class="tabler-user-plus icon-base ti icon-md me-2"></i> {{ __('Create New Role') }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.roles.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Role Name') }}</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label">{{ __('Permissions') }}</label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" id="selectAll">{{ __('Select All') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">{{ __('Deselect All') }}</button>
                            </div>
                        </div>
                        <div class="row">
                            @foreach($permissions as $permission)
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="permissions[]" value="{{ $permission->id }}"
                                               id="permission-{{ $permission->id }}">
                                        <label class="form-check-label" for="permission-{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('permissions')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('dashboard.roles.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="tabler-arrow-left icon-base ti icon-md me-1"></i> {{ __('Back') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="tabler-check icon-base ti icon-md me-1"></i> {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select All / Deselect All functionality
        const selectAllBtn = document.getElementById('selectAll');
        const deselectAllBtn = document.getElementById('deselectAll');
        const permissionCheckboxes = document.querySelectorAll('input[name="permissions[]"]');

        selectAllBtn?.addEventListener('click', function() {
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        deselectAllBtn?.addEventListener('click', function() {
            permissionCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    });
</script>
@endpush

@if($errors->any())
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show error message if there are validation errors
            @if($errors->has('name') || $errors->has('permissions'))
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error") }}',
                html: `
                    @if($errors->has('name'))
                        <p>{{ $errors->first('name') }}</p>
                    @endif
                    @if($errors->has('permissions'))
                        <p>{{ $errors->first('permissions') }}</p>
                    @endif
                `,
                confirmButtonText: '{{ __("OK") }}'
            });
            @endif
        });
    </script>
    @endpush
@endif
@endsection
