{{-- Alert Row Component --}}
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
            <button type="button" class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
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
