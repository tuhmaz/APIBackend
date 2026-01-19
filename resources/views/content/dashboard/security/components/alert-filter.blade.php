{{-- Alert Filter Component --}}
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
