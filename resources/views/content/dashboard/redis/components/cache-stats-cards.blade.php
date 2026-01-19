<!-- إحصائيات سريعة -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <div class="avatar-initial bg-primary rounded">
                            <i class="bx bx-memory-card"></i>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt1">
                            <a class="dropdown-item" href="#" onclick="refreshMemoryStats()">{{ __('Refresh') }}</a>
                        </div>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">{{ __('Memory Usage') }}</span>
                <h3 class="card-title mb-2" id="memory-usage">{{ $stats['memory_usage'] ?? '0 KB' }}</h3>
                <small class="text-success fw-semibold">
                    <i class="bx bx-up-arrow-alt"></i> {{ __('Current') }}
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
                            <i class="bx bx-key"></i>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="cardOpt2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt2">
                            <a class="dropdown-item" href="#" onclick="refreshKeyStats()">{{ __('Refresh') }}</a>
                        </div>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">{{ __('Total Keys') }}</span>
                <h3 class="card-title mb-2" id="total-keys">{{ $stats['keys_count'] ?? 0 }}</h3>
                <small class="text-success fw-semibold">
                    <i class="bx bx-up-arrow-alt"></i> {{ __('Active') }}
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
                            <i class="bx bx-target-lock"></i>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                            <a class="dropdown-item" href="#" onclick="refreshHitRatio()">{{ __('Refresh') }}</a>
                        </div>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">{{ __('Hit Ratio') }}</span>
                <h3 class="card-title mb-2" id="hit-ratio">{{ $stats['hit_ratio'] ?? 0 }}%</h3>
                <small class="text-{{ ($stats['hit_ratio'] ?? 0) > 80 ? 'success' : (($stats['hit_ratio'] ?? 0) > 60 ? 'warning' : 'danger') }} fw-semibold">
                    <i class="bx bx-{{ ($stats['hit_ratio'] ?? 0) > 80 ? 'up' : 'down' }}-arrow-alt"></i> 
                    {{ ($stats['hit_ratio'] ?? 0) > 80 ? __('Excellent') : (($stats['hit_ratio'] ?? 0) > 60 ? __('Good') : __('Needs Improvement')) }}
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
                            <i class="bx bx-time"></i>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                            <a class="dropdown-item" href="#" onclick="refreshTTLStats()">{{ __('Refresh') }}</a>
                        </div>
                    </div>
                </div>
                <span class="fw-semibold d-block mb-1">{{ __('Avg TTL') }}</span>
                <h3 class="card-title mb-2" id="avg-ttl">{{ $stats['avg_ttl'] ?? 0 }}s</h3>
                <small class="text-info fw-semibold">
                    <i class="bx bx-time-five"></i> {{ __('Seconds') }}
                </small>
            </div>
        </div>
    </div>
</div>

<script>
function refreshMemoryStats() {
    fetch('{{ route("dashboard.redis.stats") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('memory-usage').textContent = data.memory_usage;
        });
}

function refreshKeyStats() {
    fetch('{{ route("dashboard.redis.stats") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-keys').textContent = data.keys_count;
        });
}

function refreshHitRatio() {
    fetch('{{ route("dashboard.redis.stats") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('hit-ratio').textContent = data.hit_ratio + '%';
        });
}

function refreshTTLStats() {
    fetch('{{ route("dashboard.redis.stats") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('avg-ttl').textContent = data.avg_ttl + 's';
        });
}
</script>
