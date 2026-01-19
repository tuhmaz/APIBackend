@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Permission Details'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="tabler-key icon-base ti icon-md me-2"></i> {{ __('Permission Details') }}
                    </h5>
                    <div class="btn-group">
                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-warning">
                            <i class="tabler-edit icon-base ti icon-md me-1"></i> {{ __('Edit') }}
                        </a>
                        <form action="{{ route('permissions.destroy', $permission) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger ms-1" 
                                    onclick="return confirm('{{ __('Are you sure you want to delete this permission?') }}')">
                                <i class="tabler-trash icon-base ti icon-md me-1"></i> {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Permission Name') }}</label>
                            <p class="form-control-plaintext">{{ $permission->name }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Guard Name') }}</label>
                            <p class="form-control-plaintext">{{ $permission->guard_name }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Created At') }}</label>
                            <p class="form-control-plaintext">
                                {{ $permission->created_at->format('M d, Y h:i A') }}
                                <small class="text-muted">({{ $permission->created_at->diffForHumans() }})</small>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Last Updated') }}</label>
                            <p class="form-control-plaintext">
                                {{ $permission->updated_at->format('M d, Y h:i A') }}
                                <small class="text-muted">({{ $permission->updated_at->diffForHumans() }})</small>
                            </p>
                        </div>
                    </div>
                </div>

                @if($permission->roles->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3">{{ __('Roles with this Permission') }}</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($permission->roles as $role)
                            <span class="badge bg-label-primary">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">
                    <i class="tabler-arrow-left icon-base ti icon-md me-2"></i> {{ __('Back to Permissions') }}
                </a>
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
