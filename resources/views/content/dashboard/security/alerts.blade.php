@extends('layouts/contentNavbarLayout')

@section('title', 'التنبيهات الأمنية')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])

@endsection

@section('page-style')
@vite('resources/assets/css/alerts.css')
@endsection

@section('content')
<div class="row">
    <div class="col-12">
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
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex">
                        <button class="btn btn-sm btn-outline-secondary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                            <i class="page-icon ti tabler-filter me-1"></i>
                            <span>فلترة</span>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary refresh-alerts">
                            <i class="page-icon ti tabler-refresh me-1"></i>
                            <span>تحديث</span>
                        </button>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="text-muted me-2">عرض:</span>
                        <select class="form-select form-select-sm" style="width: auto;" id="perPage" name="per_page">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- بطاقة الفلترة -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="collapse" id="filterCollapse">
                <div class="card-body">
                    <form action="{{ route('dashboard.security.alerts') }}" method="GET" id="filter-form">
                        <div class="row g-3">
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="event_type">نوع الحدث</label>
                                <select class="form-select select2" id="event_type" name="event_type">
                                    <option value="">الكل</option>
                                    @foreach($eventTypes as $key => $value)
                                        <option value="{{ $value }}" {{ request('event_type') == $value ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="severity">مستوى الخطورة</label>
                                <select class="form-select select2" id="severity" name="severity">
                                    <option value="">الكل</option>
                                    @foreach($severityLevels as $key => $value)
                                        <option value="{{ $value }}" {{ request('severity') == $value ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="is_resolved">الحالة</label>
                                <select class="form-select select2" id="is_resolved" name="is_resolved">
                                    <option value="">الكل</option>
                                    <option value="false" {{ request('is_resolved') === 'false' ? 'selected' : '' }}>غير محلول</option>
                                    <option value="true" {{ request('is_resolved') === 'true' ? 'selected' : '' }}>محلول</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="ip">عنوان IP</label>
                                <input type="text" class="form-control" id="ip" name="ip" placeholder="أدخل عنوان IP" value="{{ request('ip') }}">
                            </div>
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="date_from">من تاريخ</label>
                                <input type="text" class="form-control flatpickr" id="date_from" name="date_from" placeholder="YYYY-MM-DD" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3 col-12">
                                <label class="form-label" for="date_to">إلى تاريخ</label>
                                <input type="text" class="form-control flatpickr" id="date_to" name="date_to" placeholder="YYYY-MM-DD" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="page-icon ti tabler-filter me-1"></i>
                                    <span>تطبيق الفلتر</span>
                                </button>
                                <a href="{{ route('dashboard.security.alerts') }}" class="btn btn-outline-secondary">
                                    <i class="page-icon ti tabler-refresh me-1"></i>
                                    <span>إعادة تعيين</span>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- جدول التنبيهات -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-datatable table-responsive">
                <table class="table datatables-basic table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>نوع الحدث</th>
                            <th>الوصف</th>
                            <th>عنوان IP</th>
                            <th>المستخدم</th>
                            <th>الخطورة</th>
                            <th>درجة الخطر</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alerts as $alert)
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ $alert->id }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $alert->event_type_color }} alert-badge">{{ $alert->event_type }}</span>
                                </td>
                                <td>
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $alert->description }}">
                                        {{ \Illuminate\Support\Str::limit($alert->description, 30) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-secondary">
                                                <i class="page-icon ti tabler-world"></i>
                                            </span>
                                        </div>
                                        <a href="{{ route('dashboard.security.ip-details', $alert->ip_address) }}" class="text-primary">
                                            {{ $alert->ip_address }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if($alert->user)
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ substr($alert->user->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <a href="{{ route('dashboard.users.show', $alert->user->id) }}" class="text-primary">
                                                {{ \Illuminate\Support\Str::limit($alert->user->name, 15) }}
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                @if($alert->severity == 'critical')
                                    <span class="badge bg-label-danger">حرج</span>
                                @elseif($alert->severity == 'danger')
                                    <span class="badge bg-label-danger">خطر</span>
                                @elseif($alert->severity == 'warning')
                                    <span class="badge bg-label-warning">تحذير</span>
                                @else
                                    <span class="badge bg-label-info">معلومات</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-medium me-1">{{ $alert->risk_score }}</span>
                                    <div class="progress w-100" style="height: 6px">
                                        <div
                                            class="progress-bar"
                                            style="width: {{ $alert->risk_score }}%; background-color:
                                                {{ $alert->risk_score >= 70 ? '#ea5455' : ($alert->risk_score >= 40 ? '#ff9f43' : '#28c76f') }}"
                                            role="progressbar"
                                            aria-valuenow="{{ $alert->risk_score }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($alert->is_resolved)
                                    <span class="badge bg-label-success">محلول</span>
                                @else
                                    <span class="badge bg-label-danger">غير محلول</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">{{ $alert->created_at->format('Y-m-d') }}</span>
                                    <small class="text-muted">{{ $alert->created_at->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="page-icon ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('dashboard.security.alerts.show', $alert->id) }}">
                                            <i class="page-icon ti tabler-eye me-1"></i>
                                            <span>عرض التفاصيل</span>
                                        </a>
                                        @if(!$alert->is_resolved)
                                            <a class="dropdown-item resolve-alert" href="javascript:void(0);" data-id="{{ $alert->id }}">
                                                <i class="page-icon ti tabler-check me-1"></i>
                                                <span>تحديد كمحلول</span>
                                            </a>
                                        @else
                                            <a class="dropdown-item unresolve-alert" href="javascript:void(0);" data-id="{{ $alert->id }}">
                                                <i class="page-icon ti tabler-x me-1"></i>
                                                <span>تحديد كغير محلول</span>
                                            </a>
                                        @endif
                                        @if($alert->ip_address)
                                            <a class="dropdown-item block-ip" href="javascript:void(0);" data-ip="{{ $alert->ip_address }}">
                                                <i class="page-icon ti tabler-ban me-1"></i>
                                                <span>حظر عنوان IP</span>
                                            </a>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger delete-alert" href="javascript:void(0);" data-id="{{ $alert->id }}">
                                            <i class="page-icon ti tabler-trash me-1"></i>
                                            <span>حذف</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <span class="text-muted me-2">إظهار</span>
                    <span class="fw-medium">{{ $alerts->firstItem() }} إلى {{ $alerts->lastItem() }}</span>
                    <span class="text-muted me-2">من أصل</span>
                    <span class="fw-medium">{{ $alerts->total() }}</span>
                    <span class="text-muted me-2">نتائج</span>
                </div>
                <div>
                    {{ $alerts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
