@extends('content.dashboard.monitoring.layout')

@section('title', __('Security Logs'))

@push('styles')
  <style>
    .security-card {
      transition: transform 0.2s;
      border-radius: 0.5rem;
    }

    .security-card:hover {
      transform: translateY(-3px);
    }

    .log-badge {
      font-size: 0.75rem;
      padding: 0.35em 0.65em;
      border-radius: 50rem;
    }
  </style>
@endpush

@section('monitoring-content')
  <div class="row">
    <!-- Stats Cards -->
    <div class="col-12 mb-4">
      <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card security-card bg-label-primary">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Total Events') }}</h6>
                  <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                </div>
                <div class="avatar bg-label-primary p-2 rounded">
                  <i class='tabler-shield icon-base ti icon-md'></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card security-card bg-label-danger">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Critical Events') }}</h6>
                  <h3 class="mb-0 text-danger">{{ number_format($stats['critical']) }}</h3>
                </div>
                <div class="avatar bg-label-danger p-2 rounded">
                  <i class='tabler-face-id-error icon-base ti icon-md'></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card security-card bg-label-warning">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Warnings') }}</h6>
                  <h3 class="mb-0 text-warning">{{ number_format($stats['warnings']) }}</h3>
                </div>
                <div class="avatar bg-label-warning p-2 rounded">
                  <i class='tabler-alert-circle icon-base ti icon-md'></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
          <div class="card security-card bg-label-secondary">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">{{ __('Banned IPs') }}</h6>
                  <h3 class="mb-0">{{ number_format($stats['banned_ips']) }}</h3>
                </div>
                <div class="avatar bg-label-secondary p-2 rounded">
                  <i class='tabler-shield-cancel icon-base ti icon-md'></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Security Logs') }}</h5>
          <div class="d-flex gap-2">
            <form action="{{ route('dashboard.monitoring.security.index') }}" method="GET" class="me-2">
              <select name="level" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">{{ __('All Levels') }}</option>
                <option value="critical" {{ request('level') == 'critical' ? 'selected' : '' }}>{{ __('Critical') }}
                </option>
                <option value="high" {{ request('level') == 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                <option value="medium" {{ request('level') == 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                <option value="low" {{ request('level') == 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
                <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>{{ __('Info') }}</option>
              </select>
            </form>
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class='tabler-search icon-base ti icon-md me-2'></i></span>
              <input type="text" class="form-control form-control-sm" id="searchLogs"
                placeholder="{{ __('Search logs...') }}">
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th width="160">{{ __('Date') }}</th>
                <th width="120">{{ __('Level') }}</th>
                <th>{{ __('Event') }}</th>
                <th width="150">{{ __('IP Address') }}</th>
                <th width="150">{{ __('User') }}</th>
                <th width="100"></th>
              </tr>
            </thead>
            <tbody class="table-border-bottom-0">
              @forelse ($logs as $log)
                <tr>
                  <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    @php
                      // Map severity stored in DB to Bootstrap color classes
                      $levelClass = match ($log->severity) {
                          'critical' => 'danger',
                          'danger' => 'danger',
                          'warning' => 'warning',
                          'info' => 'info',
                          default => 'secondary',
                      };
                    @endphp
                    <span class="badge log-badge bg-{{ $levelClass }}">{{ ucfirst($log->severity) }}</span>
                  </td>
                  <td>{{ Str::limit(strip_tags($log->description), 50) }}</td>
                  <td>{{ $log->ip_address }}</td>
                  <td>
                    @if ($log->user)
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-xs me-2">
                          <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ substr($log->user->name, 0, 1) }}
                          </span>
                        </div>
                        <span>{{ $log->user->name }}</span>
                      </div>
                    @else
                      <span class="text-muted">{{ __('Visitor') }}</span>
                    @endif
                  </td>
                  <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown">
                        {{ __('Actions') }}
                      </button>
                      <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logDetailsModal"
                          data-log-id="{{ $log->id }}" data-log-content="{{ strip_tags($log->description) }}">
                          <i class="tabler-file-info me-1 icon-base ti icon-md"></i> {{ __('View Details') }}
                        </a>
                        @can('ban ip')
                          <a class="dropdown-item text-danger ban-ip" href="#" data-ip="{{ $log->ip_address }}"
                            data-reason="{{ __('Security violation: ') }}{{ Str::limit(strip_tags($log->description), 80) }}">
                            <i class="tabler-ban me-1 icon-base ti icon-md"></i> {{ __('Ban IP') }}
                          </a>
                        @endcan
                      </div>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                      <i class='tabler-shield icon-base ti icon-md mb-2 text-muted'></i>
                      <span class="text-muted">{{ __('No security logs found') }}</span>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($logs->hasPages())
			<div class="pagination pagination-outline-secondary">
          {{ $logs->links('components.pagination.custom') }}
        </div>
		  @endif
      </div>
    </div>
  </div>

  <!-- Log Details Modal -->
  <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">{{ __('Security Log Details') }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <pre id="logDetailsContent" class="m-0 p-3 bg-light" style="max-height: 60vh; overflow-y: auto;"></pre>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class='tabler-x me-1 icon-base ti icon-md'></i> {{ __('Close') }}
          </button>
          <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="tabler-printer me-1 icon-base ti icon-md"></i> {{ __('Print') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Ban IP Modal -->

@endsection

@push('scripts')
  @vite(['resources/assets/js/monitoring/security.js'])
@endpush
