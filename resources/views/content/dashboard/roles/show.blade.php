@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Role Details') . ': ' . $role->name)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">
                    <i class="tabler-eye icon-base ti icon-md me-2"></i> {{ __('Role Details') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">{{ __('Role Information') }}</h6>
                        <div class="d-flex">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-icon btn-outline-primary me-2">
                                <i class="tabler-edit icon-base ti icon-md"></i>
                            </a>
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('{{ __('Are you sure you want to delete this role?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-outline-danger">
                                    <i class="tabler-trash icon-base ti icon-md"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Role Name') }}</label>
                        <p class="form-control-static fw-bold">{{ $role->name }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Created At') }}</label>
                        <p class="form-control-static">{{ $role->created_at->format('M d, Y H:i') }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Last Updated') }}</label>
                        <p class="form-control-static">{{ $role->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="mb-3">{{ __('Assigned Permissions') }}</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($role->permissions as $permission)
                            <span class="badge bg-label-primary">
                                <i class="tabler-key icon-base ti icon-md me-1"></i>
                                {{ $permission->name }}
                            </span>
                        @empty
                            <p class="text-muted">{{ __('No permissions assigned') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                        <i class="tabler-arrow-left icon-base ti icon-md me-1"></i> {{ __('Back to Roles') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
