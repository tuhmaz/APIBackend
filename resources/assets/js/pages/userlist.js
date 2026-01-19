'use strict';

$(function() {
    // تتبع المستخدمين المحددين
    let selectedUsers = [];
    // Initialize Select2
    $('.select2').select2({
        width: '100%'
    });

    // Setup AJAX headers
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    const tableBody = $('#users-table-body');
    const pagination = $('#pagination-links');
    let loadingTimer;
    const loadingDelay = 300;

    /**
     * Load users via AJAX
     * @param {string} url - The URL to load users from.
     */
    function loadUsers(url = null) {
        // Get the base URL from the data attribute
        const baseUrl = $('#filterForm').data('users-url');
        const apiUrl = url || baseUrl;

        // Clear any existing loading timer
        if (loadingTimer) clearTimeout(loadingTimer);

        // Show loading spinner
        loadingTimer = setTimeout(() => {
            tableBody.html(`
                <tr>
                    <td colspan="4" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </td>
                </tr>
            `);
        }, loadingDelay);

        // Perform AJAX request
        $.ajax({
            url: apiUrl,
            method: 'GET',
            data: {
                role: $('#UserRole').val() || '',
                search: $('#UserSearch').val() || ''
            }
        })
        .done(function(response) {
            clearTimeout(loadingTimer);

            const $response = $(response);
            const $newTableBody = $response.find('#users-table-body');
            const $newPagination = $response.find('#pagination-links');

            if ($newTableBody.length) {
                tableBody.html($newTableBody.html());
            } else {
                tableBody.html(`
                    <tr>
                        <td colspan="4" class="text-center">
                            {{ __('No results found') }}
                        </td>
                    </tr>
                `);
            }

            if ($newPagination.length) {
                pagination.html($newPagination.html());
            }

            // Update URL without refreshing
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('role', $('#UserRole').val() || '');
            newUrl.searchParams.set('search', $('#UserSearch').val() || '');
            window.history.pushState({}, '', newUrl);
        })
        .fail(function(jqXHR) {
            clearTimeout(loadingTimer);
            tableBody.html(`
                <tr>
                    <td colspan="4" class="text-center text-danger p-3">
                        <i class="page-icon ti tabler-alert-circle me-1"></i>
                        ${
                            jqXHR.status === 0
                                ? $('#filterForm').data('network-error')
                                : $('#filterForm').data('loading-error')
                        }
                    </td>
                </tr>
            `);
        });
    }

    /**
     * Debounce function to limit the rate of function execution
     * @param {Function} func - Function to debounce.
     * @param {number} wait - Time to wait in milliseconds.
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(this, args);
            }, wait);
        };
    }

    // Event Listeners
    $('#UserRole').on('change', function() {
        loadUsers();
    });

    $('#UserSearch').on('input', debounce(function() {
        loadUsers();
    }, 500));

    $(document).on('click', '#pagination-links a', function(e) {
        e.preventDefault();
        loadUsers($(this).attr('href'));
    });

    $('#resetFiltersBtn').on('click', function() {
        $('#UserRole').val('').trigger('change');
        $('#UserSearch').val('');
        loadUsers();
    });

    $(document).on('click', '.delete-record', function(e) {
        if (!confirm($('#filterForm').data('delete-confirm'))) {
            e.preventDefault();
        }
    });

    // وظائف التحديد المتعدد
    $(document).on('change', '#select-all-users', function() {
        const isChecked = $(this).prop('checked');
        $('.user-checkbox').prop('checked', isChecked);
        updateSelectedUsers();
    });

    $(document).on('change', '.user-checkbox', function() {
        updateSelectedUsers();

        // تحديث حالة زر "تحديد الكل"
        const allChecked = $('.user-checkbox:checked').length === $('.user-checkbox').length;
        $('#select-all-users').prop('checked', allChecked);
    });

    // تحديث قائمة المستخدمين المحددين وعرض/إخفاء زر الحذف
    function updateSelectedUsers() {
        selectedUsers = [];
        $('.user-checkbox:checked').each(function() {
            selectedUsers.push($(this).val());
        });

        // إظهار أو إخفاء زر الحذف المتعدد
        if (selectedUsers.length > 0) {
            $('#delete-selected').removeClass('d-none').text(`حذف المحدد (${selectedUsers.length})`);
        } else {
            $('#delete-selected').addClass('d-none');
        }
    }

    // حذف المستخدمين المحددين
    $('#delete-selected').on('click', function() {
        if (selectedUsers.length === 0) return;

        if (confirm('هل أنت متأكد من رغبتك في حذف المستخدمين المحددين؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            $.ajax({
                url: $('#filterForm').data('users-url') + '/bulk-delete',
                method: 'POST',
                data: {
                    user_ids: selectedUsers
                },
                beforeSend: function() {
                    // إظهار مؤشر التحميل
                    $('#delete-selected').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحذف...');
                    $('#delete-selected').prop('disabled', true);
                }
            })
            .done(function(response) {
                // إظهار رسالة نجاح
                Swal.fire({
                    title: 'تم الحذف بنجاح!',
                    text: response.message || 'تم حذف المستخدمين المحددين بنجاح',
                    icon: 'success',
                    confirmButtonText: 'حسناً'
                });

                // إعادة تحميل قائمة المستخدمين
                loadUsers();

                // إعادة تعيين القائمة المحددة
                selectedUsers = [];
                $('#delete-selected').addClass('d-none');
            })
            .fail(function(jqXHR) {
                // إظهار رسالة خطأ
                Swal.fire({
                    title: 'خطأ!',
                    text: jqXHR.responseJSON?.message || 'حدث خطأ أثناء محاولة حذف المستخدمين',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            })
            .always(function() {
                // إعادة تمكين الزر
                $('#delete-selected').html('<i class="page-icon ti tabler-trash me-1"></i>حذف المحدد');
                $('#delete-selected').prop('disabled', false);
            });
        }
    });
});
