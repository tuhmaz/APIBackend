@extends('layouts/contentNavbarLayout')

@section('title', 'لوحة مراقبة الأمان')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/animate-css/animate.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('page-style')
<style>
    /* تصميم عام للوحة المراقبة */
    .security-dashboard {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
        padding: 1.5rem 1rem;
        border-radius: 0.75rem;
        min-height: calc(100vh - 4rem);
    }

    /* أنماط درجة الأمان */
    .security-score-display {
        font-size: 2.75rem;
        font-weight: 800;
        text-align: center;
        background: linear-gradient(45deg, #28c76f, #00cfe8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0.5rem 0;
        letter-spacing: -0.5px;
    }

    .security-score-container {
        position: relative;
        padding: 1.75rem;
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06);
        transition: all 0.35s ease;
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .security-score-container:hover {
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.09);
        transform: translateY(-3px);
    }

    .score-label {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.35rem;
        font-weight: 700;
        color: #4a5568;
    }

    /* أنماط عدد التنبيهات */
    .alert-count-display {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    .alert-count-critical {
        color: #ea5455;
    }

    .alert-count-warning {
        color: #ff9f43;
    }

    .alert-label {
        font-size: 1rem;
        color: #6e6b7b;
        font-weight: 500;
        margin-top: 0.5rem;
    }

    /* أنماط البطاقات */
    .security-card {
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: none;
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 6px 22px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.02);
    }

    .security-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
    }

    /* أنماط رأس البطاقة */
    .security-card .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(34, 41, 47, 0.06);
        padding: 1.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .security-card .card-title {
        color: #4a5568;
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* أنماط الجدول */
    .security-table {
        --bs-table-hover-bg: rgba(115, 103, 240, 0.06);
        border-collapse: separate;
        border-spacing: 0;
    }

    .security-table th {
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.6px;
        color: #4a5568;
        padding: 1.25rem 1.75rem;
        white-space: nowrap;
        font-weight: 600;
    }

    .security-table td {
        padding: 1.25rem 1.75rem;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
    }

    /* أنماط الشارات */
    .security-badge {
        padding: 0.6rem 1rem;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: 0.5rem;
        text-transform: capitalize;
        letter-spacing: 0.3px;
    }

    /* حاويات الرسوم البيانية */
    .chart-container {
        min-height: 320px;
        position: relative;
        padding: 1rem 0;
    }

    /* أنماط الأزرار */
    .btn-wave {
        position: relative;
        overflow: hidden;
        font-weight: 600;
        letter-spacing: 0.3px;
        padding: 0.65rem 1.25rem;
    }

    .btn-wave .wave {
        position: absolute;
        display: block;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        transform: scale(0);
        animation: ripple 0.7s linear;
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(2.8);
            opacity: 0;
        }
    }

    /* أنماط الإحصائيات السريعة */
    .stats-card {
        text-align: center;
        padding: 1.5rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .stats-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
    }

    .stats-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0.5rem 0;
        color: #2d3748;
    }

    .stats-label {
        font-size: 0.95rem;
        color: #718096;
        font-weight: 500;
    }

    /* أنماط نافذة التفاصيل */
    .modal-content {
        border-radius: 1.25rem;
        border: none;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .modal-body {
        padding: 1.75rem;
    }

    .modal-footer {
        padding: 1.25rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* أنماط شريط التقدم */
    .progress {
        height: 10px;
        border-radius: 50px;
        overflow: hidden;
    }

    .progress-bar {
        border-radius: 50px;
    }

    /* أنماط القائمة المنسدلة */
    .dropdown-menu {
        border-radius: 0.75rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        padding: 0.5rem;
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin: 0.25rem 0;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: rgba(115, 103, 240, 0.1);
    }

    /* أنماط البحث */
    .input-group-text {
        background: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-left: none;
        border-radius: 0.5rem 0 0 0.5rem !important;
    }

    .form-control {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 0.65rem 1rem;
    }

    .form-control:focus {
        border-color: #7367f0;
        box-shadow: 0 3px 10px rgba(115, 103, 240, 0.1);
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y security-dashboard">
    <!-- ترويسة الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">
                        <i class="security-icon ti tabler-shield-lock me-2 text-primary"></i>
                        لوحة مراقبة الأمان
                    </h3>
                    <p class="text-muted mb-0">نظرة شاملة على حالة الأمان والthreats المحتملة</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dashboard.security.alerts') }}" class="btn btn-primary btn-wave waves-effect waves-light">
                        <i class="security-icon ti tabler-bell me-1"></i>
                        <span>التنبيهات الأمنية</span>
                        @if(isset($alertsSummary['unresolved']) && $alertsSummary['unresolved'] > 0)
                            <span class="badge bg-danger badge-notification rounded-pill ms-2">
                                {{ $alertsSummary['unresolved'] }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('dashboard.security.logs') }}" class="btn btn-outline-primary btn-wave waves-effect">
                        <i class="security-icon ti tabler-list me-1"></i>
                        <span>سجلات الأمان</span>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-wave waves-effect dropdown-toggle" type="button" id="securityActions" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="security-icon ti tabler-settings me-1"></i>
                            <span>إجراءات</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="securityActions">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="#"
                                   onclick="event.preventDefault(); document.getElementById('run-scan-form').submit();">
                                    <i class="security-icon ti tabler-shield-check me-2"></i>
                                    تشغيل فحص أمني
                                </a>
                                <form id="run-scan-form" action="{{ route('dashboard.security.run-scan') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard.security.export-report') }}">
                                    <i class="security-icon ti tabler-file-export me-2"></i>
                                    تصدير تقرير أمني
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قسم درجة الأمان والإحصائيات السريعة -->
    <div class="row">
        <!-- بطاقة درجة الأمان -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card security-card h-100 shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-shield-check me-2 text-success"></i>
                        درجة الأمان الإجمالية
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-text-secondary p-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="security-icon ti tabler-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="javascript:void(0);"><i class="security-icon ti tabler-refresh me-1"></i>تحديث</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);"><i class="security-icon ti tabler-info-circle me-1"></i>تفاصيل</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="security-score-container">
                        <div id="security-score-chart"></div>
                        <div class="score-label">
                            <div class="security-score-display">{{ isset($stats['security_score']) ? $stats['security_score'] : 85 }}</div>
                            <div class="text-muted">من أصل 100</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-center">
                            <p class="small text-muted mb-1">آخر تقييم</p>
                            <h6 class="mb-0 badge bg-label-primary p-2">{{ isset($stats['last_assessment']) ? $stats['last_assessment'] : now()->subHours(3)->format('Y-m-d H:i') }}</h6>
                        </div>
                        <div class="text-center">
                            <p class="small text-muted mb-1">التغير منذ آخر تقييم</p>
                            <h6 class="mb-0 badge {{ isset($stats['score_change']) && $stats['score_change'] >= 0 ? 'bg-label-success' : 'bg-label-danger' }} p-2">
                                @if(isset($stats['score_change']))
                                    @if($stats['score_change'] > 0)
                                        <i class="security-icon ti tabler-arrow-up me-1"></i>
                                    @elseif($stats['score_change'] < 0)
                                        <i class="security-icon ti tabler-arrow-down me-1"></i>
                                    @else
                                        <i class="security-icon ti tabler-minus me-1"></i>
                                    @endif
                                    {{ abs($stats['score_change']) }}%
                                @else
                                    <i class="security-icon ti tabler-arrow-up me-1"></i> 5%
                                @endif
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقة التنبيهات -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card security-card h-100 shadow-sm border-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-alert-triangle me-2 text-warning"></i>
                        التنبيهات الأمنية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="d-flex flex-column align-items-center">
                                <div class="alert-count-display alert-count-critical mb-0">{{ $alertsSummary['critical_alerts'] ?? 0 }}</div>
                                <span class="alert-label">تنبيهات حرجة</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex flex-column align-items-center">
                                <div class="alert-count-display alert-count-warning mb-0">{{ $alertsSummary['threshold_alerts'] ?? 0 }}</div>
                                <span class="alert-label">تنبيهات عتبة</span>
                            </div>
                        </div>
                    </div>
                    <div id="alerts-chart" class="mt-4"></div>

                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">نسبة الحل</small>
                            @php
                                $total = ($alertsSummary['unresolved'] ?? 0) + ($alertsSummary['resolved'] ?? 0);
                                $percent = $total > 0 ? round(($alertsSummary['resolved'] ?? 0) / $total * 100) : 0;
                            @endphp
                            <small class="text-success fw-bold">{{ $percent }}%</small>
                        </div>
                        <div class="progress rounded-pill" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%"
                                aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقة الإحصائيات السريعة -->
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card security-card h-100 shadow-sm border-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-chart-bar me-2 text-info"></i>
                        الإحصائيات السريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-label-primary">
                                    <i class="security-icon ti tabler-calendar-event text-primary"></i>
                                </div>
                                <div class="stats-value">{{ isset($stats['today_events']) ? $stats['today_events'] : 14 }}</div>
                                <div class="stats-label">أحداث اليوم</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-label-info">
                                    <i class="security-icon ti tabler-calendar-week text-info"></i>
                                </div>
                                <div class="stats-value">{{ isset($stats['week_events']) ? $stats['week_events'] : 87 }}</div>
                                <div class="stats-label">أحداث الأسبوع</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-label-warning">
                                    <i class="security-icon ti tabler-bug text-warning"></i>
                                </div>
                                <div class="stats-value">{{ isset($stats['unresolved_issues']) ? $stats['unresolved_issues'] : 0 }}</div>
                                <div class="stats-label">مشكلات غير محلولة</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card">
                                <div class="stats-icon bg-label-danger">
                                    <i class="security-icon ti tabler-shield-x text-danger"></i>
                                </div>
                                <div class="stats-value">{{ isset($stats['blocked_ips']) ? $stats['blocked_ips'] : 0 }}</div>
                                <div class="stats-label">عناوين IP محظورة</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قسم الرسوم البيانية -->
    <div class="row">
        <!-- اتجاهات الأحداث الأمنية -->
        <div class="col-xl-8 col-md-12 mb-4">
            <div class="card security-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-chart-line me-2 text-primary"></i>
                        اتجاهات الأحداث الأمنية
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timelineRange" data-bs-toggle="dropdown" aria-expanded="false">
                            آخر 30 يوم
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="timelineRange">
                            <li><a class="dropdown-item" href="#">آخر 7 أيام</a></li>
                            <li><a class="dropdown-item active" href="#">آخر 30 يوم</a></li>
                            <li><a class="dropdown-item" href="#">آخر 90 يوم</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="security-events-timeline" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- توزيع أنواع الأحداث -->
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card security-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-pie-chart me-2 text-success"></i>
                        توزيع أنواع الأحداث
                    </h5>
                </div>
                <div class="card-body">
                    <div id="event-type-chart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- قسم الثغرات الأمنية -->
    <div class="row">
        <div class="col-12">
            <div class="card security-card shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="security-icon ti tabler-bug me-2 text-danger"></i>
                        الثغرات الأمنية المكتشفة
                    </h5>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text"><i class="security-icon ti tabler-search"></i></span>
                            <input type="text" class="form-control" placeholder="بحث..." aria-label="بحث">
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="runSecurityScan()">
                            <i class="security-icon ti tabler-refresh me-1"></i> فحص جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table security-table align-middle">
                            <thead>
                                <tr>
                                    <th>المشكلة</th>
                                    <th>الخطورة</th>
                                    <th>الوصف</th>
                                    <th>الحل المقترح</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">مخاطر كلمات المرور</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-danger me-1">عالية</span>
                                            <i class="security-icon ti tabler-alert-triangle text-danger"></i>
                                        </div>
                                    </td>
                                    <td>تم اكتشاف كلمات مرور افتراضية في ملفات التكوين</td>
                                    <td>قم بتغيير كلمات المرور الافتراضية واستخدم متغيرات بيئية</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="security-icon ti tabler-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        <i class="security-icon ti tabler-check me-1"></i> معالجة
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#securityDetailModal" data-type="مخاطر كلمات المرور" data-description="تم اكتشاف كلمات مرور افتراضية في ملفات التكوين" data-recommendation="قم بتغيير كلمات المرور الافتراضية واستخدم متغيرات بيئية">
                                                        <i class="security-icon ti tabler-info-circle me-1"></i> تفاصيل
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning">مشكلة CSRF</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-warning me-1">متوسطة</span>
                                            <i class="security-icon ti tabler-alert-circle text-warning"></i>
                                        </div>
                                    </td>
                                    <td>بعض النماذج لا تستخدم حماية CSRF</td>
                                    <td>أضف الرمز المميز @csrf إلى جميع النماذج</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="security-icon ti tabler-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        <i class="security-icon ti tabler-check me-1"></i> معالجة
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#securityDetailModal" data-type="مشكلة CSRF" data-description="بعض النماذج لا تستخدم حماية CSRF" data-recommendation="أضف الرمز المميز @csrf إلى جميع النماذج">
                                                        <i class="security-icon ti tabler-info-circle me-1"></i> تفاصيل
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-info">تحديث الحماية</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-info me-1">منخفضة</span>
                                            <i class="security-icon ti tabler-info-circle text-info"></i>
                                        </div>
                                    </td>
                                    <td>تحديثات أمنية متاحة لحزم Composer</td>
                                    <td>قم بتنفيذ composer update لتحديث الحزم</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="security-icon ti tabler-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        <i class="security-icon ti tabler-check me-1"></i> معالجة
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#securityDetailModal" data-type="تحديث الحماية" data-description="تحديثات أمنية متاحة لحزم Composer" data-recommendation="قم بتنفيذ composer update لتحديث الحزم">
                                                        <i class="security-icon ti tabler-info-circle me-1"></i> تفاصيل
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="badge bg-danger">ثغرة SQL</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-label-danger me-1">عالية</span>
                                            <i class="security-icon ti tabler-alert-triangle text-danger"></i>
                                        </div>
                                    </td>
                                    <td>احتمالية وجود ثغرات حقن SQL في بعض الاستعلامات</td>
                                    <td>قم بتعيين كلمة مرور قوية لقاعدة البيانات في ملف .env</td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="security-icon ti tabler-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        <i class="security-icon ti tabler-check me-1"></i> معالجة
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#securityDetailModal" data-type="ثغرة SQL" data-description="احتمالية وجود ثغرات حقن SQL في بعض الاستعلامات" data-recommendation="قم بتعيين كلمة مرور قوية لقاعدة البيانات في ملف .env">
                                                        <i class="security-icon ti tabler-info-circle me-1"></i> تفاصيل
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-center">
                        <a href="{{ route('dashboard.security.logs') }}"
                           class="btn btn-primary btn-wave waves-effect d-flex align-items-center">
                            <i class="security-icon ti tabler-shield-lock me-2"></i>
                            فحص أمني شامل
                        </a>
                        <a href="{{ route('dashboard.security.logs') }}"
                           class="btn btn-outline-primary ms-3 btn-wave waves-effect d-flex align-items-center">
                            <i class="security-icon ti tabler-list me-2"></i>
                            عرض جميع السجلات
                            <i class="security-icon ti tabler-chevron-left me-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تفاصيل المشكلة الأمنية -->
<div class="modal fade" id="securityDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="securityDetailTitle">تفاصيل المشكلة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="security-icon ti tabler-info-circle text-primary me-2 fs-4"></i>
                        <h6 class="mb-0">الوصف:</h6>
                    </div>
                    <p id="securityDetailDescription" class="text-body mb-0 ps-4"></p>
                </div>
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="security-icon ti tabler-bulb text-warning me-2 fs-4"></i>
                        <h6 class="mb-0">التوصية:</h6>
                    </div>
                    <p id="securityDetailRecommendation" class="text-body mb-0 ps-4"></p>
                </div>
                <div class="alert alert-primary d-flex align-items-center" role="alert">
                    <i class="security-icon ti tabler-shield me-2 fs-4"></i>
                    <div>
                        معالجة هذه الثغرة سيؤدي إلى تحسين درجة الأمان الإجمالية للنظام وحماية البيانات بشكل أفضل.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="fixIssueBtn">معالجة المشكلة</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/jquery/jquery.js',
    'resources/assets/vendor/libs/apex-charts/apexcharts.js',
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('page-script')
<script>
    // تمرير البيانات من PHP إلى JavaScript
    window.securityScore = {{ $stats['security_score'] ?? 85 }};
    window.timelineTrends = @json($timelineTrends);
    window.eventTypeDistribution = @json($eventTypeDistribution);
</script>
@vite(['resources/assets/vendor/js/security-monitor.js'])
@endsection
