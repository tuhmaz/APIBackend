@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Roles Management'))

<!-- Page Styles -->
@section('page-style')
@vite(['resources/assets/css/demo.css'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">
                    <i class="tabler-user icon-base ti icon-md me-2"></i> {{ __('Roles Management') }}
                </h5>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showDeleted" checked>
                            <label class="form-check-label" for="showDeleted">
                                <i class="tabler-eye icon-base ti icon-md me-1"></i> {{ __('Show Active') }}
                            </label>
                        </div>
                    </div>
                    <a href="{{ route('dashboard.roles.create') }}" class="btn btn-primary">
                        <i class="tabler-plus icon-base ti icon-md me-2"></i> {{ __('Add New Role') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center">
                                    <i class="tabler-user icon-base ti icon-md me-1"></i> {{ __('Role Name') }}
                                </th>
                                <th class="text-center">
                                    <i class="tabler-key icon-base ti icon-md me-1"></i> {{ __('Permissions') }}
                                </th>
                                <th class="text-center">
                                    <i class="tabler-toggle-right icon-base ti icon-md me-1"></i> {{ __('Status') }}
                                </th>
                                <th class="text-center">
                                    <i class="tabler-settings icon-base ti icon-md me-1"></i> {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="tabler-user icon-base ti icon-md"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $role->name }}</h6>
                                                <small class="text-muted">
                                                    <i class="tabler-key icon-base ti icon-md me-1"></i>
                                                    {{ $role->permissions->count() }} {{ __('Permissions') }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($role->permissions as $perm)
                                                <span class="badge bg-label-success">
                                                    <i class="tabler-check icon-base ti icon-md me-1"></i>
                                                    {{ $perm->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="status-{{ $role->id }}" checked>
                                                <label class="form-check-label" for="status-{{ $role->id }}">
                                                    <i class="tabler-toggle-right icon-base ti icon-md me-1"></i>
                                                    {{ __('Active') }}
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-icon btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="tabler-dots-vertical icon-base ti icon-md"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('dashboard.roles.edit', $role) }}">
                                                        <i class="tabler-edit icon-base ti icon-md me-2"></i> {{ __('Edit') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('dashboard.roles.destroy', $role) }}" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button class="dropdown-item text-danger" onclick="return confirm('{{ __('Are you sure you want to delete this role?') }}')">
                                                            <i class="tabler-trash icon-base ti icon-md me-2"></i> {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Page Scripts -->
@section('page-script')
@vite(['resources/assets/js/app-access-roles.js'])
@endsection
