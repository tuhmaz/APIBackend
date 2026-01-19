@extends('content.dashboard.monitoring.layout')

@section('title', __('Banned IPs Management'))

@push('styles')
<style>
    .ban-status {
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-block;
    }
    .ban-status.active {
        color: #fff;
        background-color: #ff3e1d;
    }
    .ban-status.expired {
        color: #fff;
        background-color: #8592a3;
    }
    .ban-ip {
        font-family: monospace;
        font-weight: 600;
        direction: ltr;
        display: inline-block;
    }
    .ban-reason {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ban-duration {
        white-space: nowrap;
    }
    .stats-card {
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-card .card-body {
        padding: 1rem;
    }
    .stats-card h6 {
        font-size: 0.875rem;
        color: #697a8d;
    }
    .stats-card h3 {
        font-size: 1.5rem;
        margin-bottom: 0;
    }
    .table-responsive {
        overflow-x: auto;
    }
    /* Improved form-select styling */
    .form-select {
        border-radius: 0.375rem;
        border: 1px solid #d9dee3;
        padding: 0.6rem 1rem;
        font-size: 0.9375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .form-select:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.25);
    }
    .input-group-text {
        background-color: #f5f5f9;
        border: 1px solid #d9dee3;
        color: #697a8d;
        font-weight: 500;
    }
    .input-group {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@section('monitoring-content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Banned IPs Management') }}</h5>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#banIpModal">
                        <i class='tabler-plus me-1 icon-base ti icon-md'></i> {{ __('Add New Ban') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4 g-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="card stats-card bg-label-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ __('Total Bans') }}</h6>
                                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card stats-card bg-label-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ __('Active Bans') }}</h6>
                                        <h3 class="mb-0">{{ $stats['active'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card stats-card bg-label-secondary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ __('Expired Bans') }}</h6>
                                        <h3 class="mb-0">{{ $stats['expired'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card stats-card bg-label-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ __('Permanent Bans') }}</h6>
                                        <h3 class="mb-0">{{ $stats['permanent'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <label class="input-group-text" for="statusFilter">{{ __('Status') }}</label>
                            <select class="form-select" id="statusFilter" onchange="this.form.submit()">
                                <option value="">{{ __('All') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                                <option value="permanent" {{ request('status') === 'permanent' ? 'selected' : '' }}>{{ __('Permanent') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card overflow-visible">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Banned IPs List') }}</h5>
                        <div class="d-flex">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class='tabler-search icon-base ti icon-md'></i></span>
                                <input type="text" class="form-control" id="searchBans" placeholder="{{ __('Search...') }}" style="width: 200px;">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive overflow-visible">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Start Date') }}</th>
                                    <th>{{ __('End Date') }}</th>
                                    <th>{{ __('Banned By') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($bans as $ban)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="{{ $ban->id }}">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ban-ip">{{ $ban->ip }}</span>
                                    </td>
                                    <td>
                                        <span class="ban-status {{ $ban->isActive() ? 'active' : 'expired' }}">
                                            {{ $ban->isActive() ? __('Active') : __('Expired') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ban-reason" data-bs-toggle="tooltip" title="{{ $ban->reason }}">
                                            {{ $ban->reason ?: __('No reason specified') }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ban-duration">{{ $ban->created_at ? $ban->created_at->format('Y-m-d H:i') : '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="ban-duration">
                                            {{ $ban->banned_until ? $ban->banned_until->format('Y-m-d H:i') : __('Permanent') }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $ban->admin->name ?? __('System') }}
                                    </td>
                                    <td>
                                        <div class="dropdown position-static">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                                                <i class="bx bx-dots-vertical-rounded"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                @if($ban->isActive())
                                                    <form action="{{ route('dashboard.monitoring.bans.unban', $ban->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item" onclick="return confirm('{{ __('Are you sure you want to unban this IP?') }}')">
                                                            <i class="bx bx-check-circle me-1"></i> {{ __('Unban') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('dashboard.monitoring.bans.destroy', $ban->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('{{ __('Are you sure you want to delete this ban?') }}')">
                                                        <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class='bx bx-shield-quarter bx-lg mb-2 text-muted'></i>
                                            <span class="text-muted">{{ __('No banned IPs found') }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $bans->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Ban Modal -->
<div class="modal fade" id="banIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('dashboard.monitoring.bans.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Ban IP Address') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ip" class="form-label">{{ __('IP Address') }}</label>
                        <input type="text" class="form-control" id="ip" name="ip" placeholder="{{ __('e.g., 192.168.1.1') }}" required>
                        <div class="form-text">{{ __('Enter IP address or range (e.g., 192.168.1.1 or 192.168.1.0/24)') }}</div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">{{ __('Reason') }}</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="{{ __('Reason for banning this IP') }}" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Ban Duration') }}</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="duration" id="duration1d" value="1d" checked>
                            <label class="btn btn-outline-primary" for="duration1d">{{ __('1 Day') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="duration1w" value="1w">
                            <label class="btn btn-outline-primary" for="duration1w">{{ __('1 Week') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="duration1m" value="1m">
                            <label class="btn btn-outline-primary" for="duration1m">{{ __('1 Month') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="duration3m" value="3m">
                            <label class="btn btn-outline-primary" for="duration3m">{{ __('3 Months') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="duration6m" value="6m">
                            <label class="btn btn-outline-primary" for="duration6m">{{ __('6 Months') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="duration1y" value="1y">
                            <label class="btn btn-outline-primary" for="duration1y">{{ __('1 Year') }}</label>

                            <input type="radio" class="btn-check" name="duration" id="durationPerm" value="permanent">
                            <label class="btn btn-outline-primary" for="durationPerm">{{ __('Permanent') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="preventRegistration" name="prevent_registration" value="1" checked>
                            <label class="form-check-label" for="preventRegistration">{{ __('Prevent registration from this IP') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="preventLogin" name="prevent_login" value="1" checked>
                            <label class="form-check-label" for="preventLogin">{{ __('Prevent login from this IP') }}</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Ban IP') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="bulkActionsForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Bulk Action') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to') }} <span id="bulkActionText">{{ __('delete') }}</span> {{ __('the selected IPs?') }}</p>
                    <div id="selectedIps" class="mt-3 p-2 bg-light rounded"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
    @vite(['resources/assets/js/monitoring/bans.js'])

    <script>
        // Initialize Bootstrap Dropdowns explicitly (defensive)
        document.addEventListener('DOMContentLoaded', function () {
            var dropdownTriggerList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownTriggerList.forEach(function (dropdownToggleEl) {
                try { new bootstrap.Dropdown(dropdownToggleEl); } catch(e) {}
            });
        });
        // Global functions that might be used in other scripts
        window.formatDate = function(dateString) {
            return moment(dateString).format('YYYY-MM-DD HH:mm:ss');
        };

        window.timeAgo = function(dateString) {
            return moment(dateString).fromNow();
        };
    </script>
@endsection

@push('scripts')
