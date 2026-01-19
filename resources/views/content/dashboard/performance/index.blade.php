@extends('layouts/contentNavbarLayout')

@section('title', __('System Performance'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('page-style')
@vite(['resources/assets/css/pages/performance.css'])
@endsection

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Header / Controls -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <div>
            <h4 class="mb-1">{{ __('System Performance') }}</h4>
            <small class="text-muted" id="last-updated">{{ __('Last Updated') }}: <span></span></small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch me-2">
                <input class="form-check-input" type="checkbox" id="autoRefreshToggle">
                <label class="form-check-label" for="autoRefreshToggle">{{ __('Auto Refresh') }}</label>
            </div>
            <button class="btn btn-sm btn-primary d-flex align-items-center" id="refreshNow">
                <i class="performance-icon ti tabler-refresh me-1"></i><span>{{ __('Refresh Now') }}</span>
            </button>
        </div>
    </div>

    <!-- KPI Tiles -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block">{{ __('CPU') }}</span>
                        <h4 class="mb-0" id="kpi-cpu">0%</h4>
                        <small class="text-muted">{{ __('Cores') }}: <span id="cpu-cores"></span></small>
                    </div>
                    <div id="kpiCpuSparkline"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block">{{ __('Memory') }}</span>
                        <h4 class="mb-0" id="kpi-memory">0%</h4>
                        <small class="text-muted">{{ __('Used') }}: <span id="memory-used"></span></small>
                    </div>
                    <div id="kpiMemorySparkline"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block">{{ __('Disk') }}</span>
                        <h4 class="mb-0" id="kpi-disk">0%</h4>
                        <small class="text-muted">{{ __('Free') }}: <span id="disk-free"></span></small>
                    </div>
                    <div id="kpiDiskSparkline"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted d-block">{{ __('DB Size') }}</span>
                        <h4 class="mb-0" id="kpi-db-size">0</h4>
                        <small class="text-muted">{{ __('Uptime') }}: <span id="uptime"></span></small>
                    </div>
                    <i class="performance-icon ti tabler-database text-muted fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="row g-4">
        <!-- Left: Resource Overview -->
        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('Resource Overview') }}</h5>
                    <small class="text-muted">{{ __('Live trends') }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <div id="cpuChart"></div>
                            <div class="row mt-3 g-3">
                                <div class="col-sm-4"><span class="text-muted">{{ __('Usage') }}:</span> <span id="cpu-usage"></span></div>
                                <div class="col-sm-4"><span class="text-muted">{{ __('Load Average') }}:</span> <span id="cpu-load"></span></div>
                                <div class="col-sm-4"><span class="text-muted">{{ __('Cores') }}:</span> <span id="cpu-cores-dup"></span></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div id="memoryChart"></div>
                            <div class="row mt-3 g-3">
                                <div class="col-sm-4"><span class="text-muted">{{ __('Total') }}:</span> <span id="memory-total"></span></div>
                                <div class="col-sm-4"><span class="text-muted">{{ __('Used') }}:</span> <span id="memory-used-dup"></span></div>
                                <div class="col-sm-4"><span class="text-muted">{{ __('Free') }}:</span> <span id="memory-free"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Disk Usage') }}</h5>
                        </div>
                        <div class="card-body">
                            <div id="diskChart"></div>
                            <div class="mt-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted">{{ __('Total') }}</span>
                                    <span id="disk-total"></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted">{{ __('Used') }}</span>
                                    <span id="disk-used"></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" id="disk-progress" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('Load Average') }}</h5>
                        </div>
                        <div class="card-body">
                            <div id="loadChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: System Information -->
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('System Information') }}</h5>
                    <i class="performance-icon ti tabler-info-circle text-muted"></i>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('OS') }}</span>
                            <span id="system-info" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('PHP') }}</span>
                            <span id="php-version" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('Laravel') }}</span>
                            <span id="laravel-version" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('Server') }}</span>
                            <span id="server-software" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('Database') }}</span>
                            <span id="db-type" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('DB Name') }}</span>
                            <span id="db-name" class="fw-medium"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">{{ __('DB Size') }}</span>
                            <span id="db-size" class="fw-medium"></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages/performance.js' ])
@endsection
