@extends('layouts/layoutMaster')

@section('title', __('Cache System Monitoring'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js','resources/assets/vendor/js/optimizeCache.js',
])
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">{{ __('Cache System Monitoring') }}</h4>
        <div>
            <button class="btn btn-primary" onclick="window.refreshData()">
                <i class="redis-icon ti tabler-refresh"></i> {{ __('Refresh') }}
            </button>
            <button class="btn btn-success" onclick="window.optimizeCache()">
                <i class="redis-icon ti tabler-filter-cog"></i> {{ __('Optimize') }}
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-primary rounded">
                                <i class="redis-icon ti tabler-square-rotated-forbid-2"></i>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ __('Memory Usage') }}</span>
                    <h3 class="card-title mb-2" id="memory-usage">{{ $stats['memory_usage'] ?? '0 KB' }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="redis-icon ti tabler-arrow-autofit-content me-1"></i> {{ __('Current') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-success rounded">
                                <i class="redis-icon ti tabler-key"></i>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ __('Total Keys') }}</span>
                    <h3 class="card-title mb-2" id="total-keys">{{ $stats['keys_count'] ?? 0 }}</h3>
                    <small class="text-success fw-semibold">
                        <i class="redis-icon ti tabler-trending-up-2"></i> {{ __('Active') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-warning rounded">
                                <i class="redis-icon ti tabler-target-off"></i>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ __('Hit Ratio') }}</span>
                    <h3 class="card-title mb-2" id="hit-ratio">{{ $stats['hit_ratio'] ?? 0 }}%</h3>
                    <small class="text-success fw-semibold">
                        <i class="redis-icon ti tabler-trending-up-3"></i> {{ __('Efficiency') }}
                    </small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-info rounded">
                                <i class="redis-icon ti tabler-clock-2"></i>
                            </div>
                        </div>
                    </div>
                    <span class="fw-semibold d-block mb-1">{{ __('Avg TTL') }}</span>
                    <h3 class="card-title mb-2" id="avg-ttl">{{ $stats['avg_ttl'] ?? 0 }}s</h3>
                    <small class="text-success fw-semibold">
                        <i class="redis-icon ti tabler-trending-up-3"></i> {{ __('Seconds') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="row mb-4">
        <div class="col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">{{ __('Performance Trends') }}</h5>
                </div>
                <div class="card-body">
                    <div id="performanceChart"></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">{{ __('Memory Usage Distribution') }}</h5>
                </div>
                <div class="card-body">
                    <div id="memoryChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- تقرير الأداء -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">{{ __('Performance Report') }}</h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            {{ __('Last 24 Hours') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="window.loadReport(1)">{{ __('Last Hour') }}</a></li>
                            <li><a class="dropdown-item" href="#" onclick="window.loadReport(24)">{{ __('Last 24 Hours') }}</a></li>
                            <li><a class="dropdown-item" href="#" onclick="window.loadReport(168)">{{ __('Last Week') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Metric') }}</th>
                                    <th>{{ __('Value') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody id="performance-table">
                                <tr>
                                    <td>{{ __('Total Operations') }}</td>
                                    <td id="total-operations">{{ $report['summary']['total_operations'] ?? 0 }}</td>
                                    <td><span class="badge bg-success">{{ __('Normal') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('Cache Hits') }}</td>
                                    <td id="cache-hits">{{ $report['summary']['cache_hits'] ?? 0 }}</td>
                                    <td><span class="badge bg-success">{{ __('Good') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('Cache Misses') }}</td>
                                    <td id="cache-misses">{{ $report['summary']['cache_misses'] ?? 0 }}</td>
                                    <td><span class="badge bg-warning">{{ __('Monitor') }}</span></td>
                                </tr>
                                <tr>
                                    <td>{{ __('Average Response Time') }}</td>
                                    <td id="avg-response-time">{{ round($report['summary']['avg_response_time'] ?? 0, 2) }} ms</td>
                                    <td><span class="badge bg-success">{{ __('Fast') }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- أهم المفاتيح -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">{{ __('Top Cache Keys') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Key') }}</th>
                                    <th>{{ __('Operations') }}</th>
                                    <th>{{ __('Avg Response Time') }}</th>
                                    <th>{{ __('Memory Usage') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="top-keys-table">
                                @if(isset($report['top_keys']) && count($report['top_keys']) > 0)
                                    @foreach($report['top_keys'] as $key)
                                    <tr>
                                        <td>
                                            <code>{{ Str::limit($key['key'], 50) }}</code>
                                        </td>
                                        <td>{{ number_format($key['operations']) }}</td>
                                        <td>{{ round($key['avg_response_time'], 2) }} ms</td>
                                        <td>{{ number_format($key['total_memory']) }} KB</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="window.analyzeKey('{{ $key['key'] }}')">
                                                {{ __('Analyze') }}
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center">{{ __('No data available') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- توصيات التحسين -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">{{ __('Optimization Recommendations') }}</h5>
                </div>
                <div class="card-body">
                    <div id="recommendations-container">
                        @if(isset($recommendations) && count($recommendations) > 0)
                            @foreach($recommendations as $recommendation)
                            <div class="alert alert-{{ $recommendation['type'] == 'cleanup' ? 'warning' : ($recommendation['type'] == 'performance' ? 'info' : 'primary') }} alert-dismissible">
                                <h6 class="alert-heading">
                                    @if($recommendation['type'] == 'cleanup')
                                        <i class="bx bx-trash"></i> {{ __('Cleanup Required') }}
                                    @elseif($recommendation['type'] == 'performance')
                                        <i class="bx bx-tachometer"></i> {{ __('Performance Issue') }}
                                    @else
                                        <i class="bx bx-memory-card"></i> {{ __('Memory Optimization') }}
                                    @endif
                                </h6>
                                <p class="mb-2">{{ $recommendation['message'] }}</p>
                                @if(isset($recommendation['keys']) && count($recommendation['keys']) > 0)
                                    <ul class="mb-2">
                                        @foreach(array_slice($recommendation['keys'], 0, 3) as $key)
                                            <li><code>{{ is_array($key) ? ($key['key'] ?? $key['cache_key'] ?? 'Unknown') : $key }}</code></li>
                                        @endforeach
                                    </ul>
                                @endif
                                <button class="btn btn-sm btn-outline-primary" onclick="window.applyRecommendation('{{ $recommendation['action'] }}')">
                                    {{ __('Apply Fix') }}
                                </button>
                            </div>
                            @endforeach
                        @else
                            <div class="alert alert-success">
                                <i class="bx bx-check-circle"></i> {{ __('No optimization issues found. System is running efficiently.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal لتحليل المفتاح -->
<div class="modal fade" id="keyAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Key Analysis') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="key-analysis-content">
                <!-- سيتم تحميل المحتوى هنا -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
// تمرير بيانات الرسوم البيانية إلى JavaScript
window.chartData = @json($chartData ?? []);
</script>
@endsection
