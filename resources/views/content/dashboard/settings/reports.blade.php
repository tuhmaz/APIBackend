@extends('layouts/layoutMaster')

@section('title', 'تقارير الإساءة')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
@endsection

@section('page-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
  <!-- تحميل Bootstrap JS بشكل صحيح -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // عناصر أساسية
      const modalEl = document.getElementById('reportModal');
      const detailsEl = document.getElementById('report-details');
      const messageForm = document.getElementById('reportMessageForm');
      const bodyInput = document.getElementById('report_message_body');
      const reportIdInput = document.getElementById('report_id');
      const ipBanLink = document.getElementById('ipBanLink');
      let bsModal = null;

      // تفعيل Bootstrap Modal
      if (window.bootstrap && modalEl) {
        bsModal = new bootstrap.Modal(modalEl);
      }

      // فتح تفاصيل تقرير
      function openReport(reportId) {
        if (!bsModal) {
          window.Swal?.fire({ icon: 'error', title: 'حدث خطأ في تحميل النافذة' });
          return;
        }
        reportIdInput.value = reportId;
        detailsEl.innerHTML = '<div class="text-center py-4 text-muted">جاري التحميل...</div>';
        fetch(`{{ url('dashboard/settings/reports') }}/${reportId}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => {
          detailsEl.innerHTML = `
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="fw-bold mb-1">المبلِّغ</div>
                  <div>الاسم: ${d.reporter?.name ?? ''} (ID: ${d.reporter?.id ?? ''})</div>
                  <div>البريد: ${d.reporter?.email ?? ''}</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="fw-bold mb-1">المُبلَّغ عنه</div>
                  <div>الاسم: ${d.reported?.name ?? ''} (ID: ${d.reported?.id ?? ''})</div>
                  <div>البريد: ${d.reported?.email ?? ''}</div>
                </div>
              </div>
              <div class="col-12">
                <div class="border rounded p-3">
                  <div class="fw-bold mb-1">السبب</div>
                  <div style="white-space: pre-wrap;">${d.reason ?? ''}</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="fw-bold mb-1">الحالة</div>
                  <div>${d.status}</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                  <div class="fw-bold mb-1">التاريخ</div>
                  <div>${d.created_at}</div>
                </div>
              </div>
            </div>
          `;
          const userId = d.reported?.id ?? '';
          ipBanLink.href = `{{ url('dashboard/monitoring/bans') }}?user_id=${encodeURIComponent(userId)}`;
        })
        .catch(() => {
          detailsEl.innerHTML = '<div class="text-danger">تعذّر جلب تفاصيل التقرير.</div>';
        });

        bsModal.show();
      }

      // تفعيل فتح المودال عند النقر على الصف
      document.querySelectorAll('tr[data-report-id]').forEach(tr => {
        tr.addEventListener('click', () => openReport(tr.dataset.reportId));
      });

      // إرسال رسالة من داخل النافذة
      messageForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const reportId = reportIdInput.value;
        const body = bodyInput.value.trim();
        if (!body) return;
        fetch(`{{ url('dashboard/settings/reports') }}/${reportId}/message`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ body })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            bodyInput.value = '';
            window.Swal?.fire({ icon: 'success', title: 'تم الإرسال', timer: 1200, showConfirmButton: false });
          } else {
            window.Swal?.fire({ icon: 'error', title: 'تعذّر الإرسال' });
          }
        })
        .catch(() => window.Swal?.fire({ icon: 'error', title: 'تعذّر الإرسال' }));
      });
      // زر إنهاء النزاع => تغيير الحالة إلى complet
      const markCompleteBtn = document.getElementById('markCompleteBtn');
      markCompleteBtn?.addEventListener('click', () => {
        const reportId = reportIdInput.value;
        if (!reportId) return;
        fetch(`{{ url('dashboard/settings/reports') }}/${reportId}/status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ status: 'complet' })
        })
          .then(r => r.json())
          .then(d => {
            if (d.success) {
              // تحديث البادج في الجدول مباشرة
              const row = document.querySelector(`tr[data-report-id="${reportId}"]`);
              if (row) {
                const statusCell = row.querySelector('td:nth-child(5) span');
                if (statusCell) {
                  statusCell.textContent = d.status;
                  statusCell.className = 'badge bg-label-success';
                }
              }
              window.Swal?.fire({ icon: 'success', title: 'تم إنهاء النزاع', timer: 1200, showConfirmButton: false });
            } else {
              window.Swal?.fire({ icon: 'error', title: 'تعذّر التحديث' });
            }
          })
          .catch(() => window.Swal?.fire({ icon: 'error', title: 'تعذّر التحديث' }));
      });

    });
  </script>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">قائمة التقارير</h5>
    <form class="d-flex gap-2" method="get" action="{{ route('dashboard.settings.reports') }}">
      <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث في السبب...">
      <select name="status" class="form-select">
        <option value="">كل الحالات</option>
        <option value="pending" @selected(request('status')==='pending')>Pending</option>
        <option value="reviewed" @selected(request('status')==='reviewed')>Reviewed</option>
      </select>
      <button class="btn btn-primary" type="submit">بحث</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>المبلِّغ</th>
          <th>المُبلَّغ عنه</th>
          <th>السبب</th>
          <th>الحالة</th>
          <th>التاريخ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reports as $report)
          <tr class="cursor-pointer" data-report-id="{{ $report->id }}">
            <td>{{ $report->id }}</td>
            <td>
              {{ $report->reporter?->name ?? ('User #'.$report->reporter_id) }}
              <div class="text-muted small">ID: {{ $report->reporter_id }}</div>
            </td>
            <td>
              {{ $report->reported?->name ?? ('User #'.$report->reported_user_id) }}
              <div class="text-muted small">ID: {{ $report->reported_user_id }}</div>
            </td>
            <td style="white-space: pre-wrap;max-width: 420px;">{{ $report->reason }}</td>
            <td><span class="badge bg-label-{{ $report->status === 'pending' ? 'warning' : 'success' }}">{{ $report->status }}</span></td>
            <td>{{ $report->created_at?->format('Y-m-d H:i') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center py-6">لا توجد تقارير</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($reports->hasPages())
  <div class="card-footer d-flex justify-content-center">
    {{ $reports->links() }}
  </div>
  @endif
</div>

<!-- Modal: تفاصيل التقرير -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل البلاغ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="report-details">
          <div class="text-center py-4 text-muted">جاري التحميل...</div>
        </div>
        <hr/>
        <h6 class="mb-2">إرسال رسالة إلى المُبلَّغ عنه</h6>
        <form id="reportMessageForm" class="d-flex gap-2">
          <input type="hidden" name="report_id" id="report_id" value="">
          <textarea name="body" id="report_message_body" class="form-control" rows="2" placeholder="اكتب رسالتك..." required></textarea>
          <button type="submit" class="btn btn-primary">إرسال</button>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a id="ipBanLink" href="#" class="btn btn-outline-danger" target="_blank">
          حظر عبر نظام IP BAN
        </a>
        <div class="ms-auto d-flex gap-2">
          <button type="button" id="markCompleteBtn" class="btn btn-success">إنهاء النزاع</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
