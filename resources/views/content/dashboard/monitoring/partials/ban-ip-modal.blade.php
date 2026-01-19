<!-- Ban IP Modal -->
<div class="modal fade" id="banIpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="banIpForm" action="{{ route('monitoring.bans.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">حظر عنوان IP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ip" class="form-label">عنوان IP</label>
                        <input type="text" class="form-control" id="ip" name="ip" readonly>
                        <input type="hidden" name="log_id" id="log_id">
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">السبب (اختياري)</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="السبب وراء حظر هذا العنوان"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">مدة الحظر</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration1" value="1" checked>
                            <label class="form-check-label" for="duration1">يوم واحد</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration7" value="7">
                            <label class="form-check-label" for="duration7">أسبوع واحد</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="duration30" value="30">
                            <label class="form-check-label" for="duration30">شهر واحد</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="duration" id="permanent" value="permanent">
                            <label class="form-check-label" for="permanent">دائم</label>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="prevent_registration" id="prevent_registration" value="1" checked>
                        <label class="form-check-label" for="prevent_registration">منع التسجيل من هذا العنوان</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="prevent_login" id="prevent_login" value="1" checked>
                        <label class="form-check-label" for="prevent_login">منع تسجيل الدخول من هذا العنوان</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الحظر</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ban IP modal
        const banIpModal = document.getElementById('banIpModal');
        if (banIpModal) {
            banIpModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const ip = button.getAttribute('data-ip');
                const logId = button.getAttribute('data-log-id') || '';
                
                const modal = this;
                modal.querySelector('#ip').value = ip;
                modal.querySelector('#log_id').value = logId;
                
                // Reset form
                modal.querySelector('form').reset();
                modal.querySelector('#duration1').checked = true;
                modal.querySelector('#prevent_registration').checked = true;
                modal.querySelector('#prevent_login').checked = true;
            });
        }
        
        // Handle form submission
        const banIpForm = document.getElementById('banIpForm');
        if (banIpForm) {
            banIpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'تم الحظر بنجاح', 'تم حظر عنوان IP بنجاح');
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(banIpModal);
                        modal.hide();
                        // Refresh the page
                        window.location.reload();
                    } else {
                        showToast('error', 'خطأ', data.message || 'حدث خطأ أثناء محاولة حظر عنوان IP');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'خطأ', 'حدث خطأ أثناء معالجة الطلب');
                });
            });
        }
        
        // Helper function to show toast notifications
        function showToast(type, title, message) {
            const toast = document.createElement('div');
            toast.className = `bs-toast toast fade show bg-${type} position-fixed top-0 end-0 m-3`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="bx bx-${type === 'success' ? 'check-circle' : 'error-circle'} me-2"></i>
                    <div class="me-auto fw-semibold">${title}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove toast after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    });
</script>
@endpush
