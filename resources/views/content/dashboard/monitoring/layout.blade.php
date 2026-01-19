@extends('layouts/contentNavbarLayout')

@section('title', 'نظام المراقبة')


@section('vendor-style')
  @vite(['resources/assets/vendor/libs/leaflet/leaflet.scss',
   'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
     'resources/assets/vendor/libs/select2/select2.scss',
     'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
  @parent

  <style>
    .nav-pills .nav-link.active {
      background-color: #696cff;
      color: #fff;
    }

    .card-zoom {
      transition: transform .2s;
    }

    .card-zoom:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
    }

    .visitor-flag {
      width: 24px;
      height: 16px;
      margin-right: 8px;
      border: 1px solid #e9ecef;
    }

    .online-badge {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 6px;
    }

    .online {
      background-color: #71dd37;
    }

    .offline {
      background-color: #8592a3;
    }

    .security-level {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .level-critical {
      background-color: #ff3e1d;
      color: #fff;
    }

    .level-danger {
      background-color: #ff9f43;
      color: #fff;
    }

    .level-warning {
      background-color: #ffc107;
      color: #fff;
    }

    .level-info {
      background-color: #3b82f6;
      color: #fff;
    }
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/moment/moment.js'])
  @parent

  @vite(['resources/assets/vendor/libs/leaflet/leaflet.js'])
  @parent

@endsection

@section('page-script')
  @vite(['resources/assets/js/monitoring/monitoring.js'])

  <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize tooltips
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });

      // Format dates with moment.js (kept globally available for backward compatibility)
      window.formatDate = function(dateString) {
        return moment(dateString).format('YYYY-MM-DD HH:mm:ss');
      };

      // Format time ago (kept globally available for backward compatibility)
      window.timeAgo = function(dateString) {
        return moment(dateString).fromNow();
      };
    });
  </script>
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
      <span class="text-muted fw-light">@lang('Dashboard') /</span> @lang('Monitoring System')
    </h4>

    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-body">
            <ul class="nav nav-pills mb-3" role="tablist">
              <li class="nav-item">
                <a href="{{ route('dashboard.monitoring.visitors.index') }}"
                  class="nav-link {{ request()->is('dashboard/monitoring/visitors*') ? 'active' : '' }}">
                  <i class='bx bx-user me-1'></i> @lang('Active Visitors')
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.monitoring.security.index') }}"
                  class="nav-link {{ request()->is('dashboard/monitoring/security*') ? 'active' : '' }}">
                  <i class='bx bx-shield-quarter me-1'></i> @lang('Security Log')
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('dashboard.monitoring.bans.index') }}"
                  class="nav-link {{ request()->is('dashboard/monitoring/bans*') ? 'active' : '' }}">
                  <i class='bx bx-block me-1'></i> @lang('Banned IPs')
                </a>
              </li>
            </ul>
          </div>
        </div>

        @yield('monitoring-content')
      </div>
    </div>
  </div>
@endsection
