{{-- Alert Header Component --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center pb-0">
        <div class="d-flex align-items-center">
            <div class="avatar me-3">
                <div class="avatar-initial bg-label-danger rounded">
                    <i class="page-icon ti tabler-shield"></i>
                </div>
            </div>
            <div>
                <h5 class="mb-1">التنبيهات الأمنية</h5>
                <small class="text-muted">{{ $alerts->total() }} تنبيه</small>
            </div>
        </div>
        <div class="d-flex">
            <a href="{{ route('dashboard.security.monitor') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="page-icon ti tabler-chart-pie me-1"></i>
                <span>المراقبة</span>
            </a>
            <a href="{{ route('dashboard.security.logs') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="page-icon ti tabler-list me-1"></i>
                <span>السجلات</span>
            </a>
            <a href="{{ route('dashboard.security.export-report') }}" class="btn btn-outline-secondary btn-sm">
                <i class="page-icon ti tabler-download me-1"></i>
                <span>تصدير</span>
            </a>
        </div>
    </div>
</div>
