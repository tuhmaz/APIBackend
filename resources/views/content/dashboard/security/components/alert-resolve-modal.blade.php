{{-- Alert Resolve Modal Component --}}
<div class="modal fade" id="resolveAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديد التنبيه كمحلول</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resolveAlertForm" method="POST">
                    <input type="hidden" id="alert_id" name="alert_id">
                    <div class="mb-3">
                        <label class="form-label">ملاحظات الحل</label>
                        <textarea class="form-control" name="resolution_notes" rows="3" required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
