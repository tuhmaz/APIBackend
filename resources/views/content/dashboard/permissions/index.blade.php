@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', __('Permissions Management'))

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
                    <i class="tabler-key icon-base ti icon-md me-2"></i> {{ __('Permissions Management') }}
                </h5>
                <div class="d-flex justify-content-end">
                    <a href="{{ route('dashboard.permissions.create') }}" class="btn btn-primary">
                        <i class="tabler-plus icon-base ti icon-md me-2"></i> {{ __('Add New Permission') }}
                    </a>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-permissions table border-top">
                    <thead>
                        <tr>
                            <th class="text-center">
                                <i class="tabler-key icon-base ti icon-md me-1"></i> {{ __('Permission Name') }}
                            </th>
                            <th class="text-center">
                                <i class="tabler-calendar icon-base ti icon-md me-1"></i> {{ __('Created At') }}
                            </th>
                            <th class="text-center">
                                <i class="tabler-settings icon-base ti icon-md me-1"></i> {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                        <tr>
                            <td class="text-center">
                                <div class="d-flex justify-content-start align-items-center">
                                    <div class="avatar-wrapper">
                                        <div class="avatar me-2">
                                            <span class="avatar-initial rounded-circle bg-label-primary">
                                                <i class="tabler-key icon-base ti icon-md"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-heading fw-medium">{{ $permission->name }}</span>
                                        <small class="text-muted">{{ $permission->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-muted">{{ $permission->created_at->format('M d, Y') }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-block">
                                    <a href="{{ route('dashboard.permissions.edit', $permission) }}" class="btn btn-sm btn-icon btn-outline-primary me-1">
                                        <i class="tabler-edit icon-base ti icon-md"></i>
                                    </a>
                                    <form action="{{ route('dashboard.permissions.destroy', $permission) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger"
                                            onclick="return confirm('{{ __('Are you sure you want to delete this permission?') }}')">
                                            <i class="tabler-trash icon-base ti icon-md"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="tabler-alert-circle icon-base ti icon-xxl text-muted mb-2"></i>
                                    <p class="text-muted mb-0">{{ __('No permissions found') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize DataTable if needed
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('.datatables-permissions')) {
            const dt_permissions = $('.datatables-permissions').DataTable({
                order: [[0, 'asc']],
                dom: '<"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json',
                    search: '',
                    searchPlaceholder: '{{ __("Search permissions...") }}',
                },
                columnDefs: [
                    {
                        targets: -1,
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        }

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
    });
</script>
@endpush
