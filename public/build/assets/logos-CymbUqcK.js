var l;const c=(l=document.querySelector('meta[name="csrf-token"]'))==null?void 0:l.getAttribute("content");$(function(){const i=$("#security-logs-table");if(!i.length)return;const n=i.DataTable({dom:'<"card-header"<"head-label text-center"><"dt-action-buttons text-end"B>><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',buttons:[{extend:"collection",className:"btn btn-label-secondary dropdown-toggle mx-3",text:'<i class="logos-icon ti tabler-screen-share me-1 ti-xs"></i>تصدير',buttons:[{extend:"print",text:'<i class="logos-icon ti tabler-printer me-2" ></i>طباعة',className:"dropdown-item",exportOptions:{columns:[0,1,2,3,4]}},{extend:"csv",text:'<i class="logos-icon ti tabler-file-text me-2" ></i>CSV',className:"dropdown-item",exportOptions:{columns:[0,1,2,3,4]}},{extend:"excel",text:'<i class="logos-icon ti tabler-file-spreadsheet me-2"></i>Excel',className:"dropdown-item",exportOptions:{columns:[0,1,2,3,4]}},{extend:"pdf",text:'<i class="logos-icon ti tabler-file-code-2 me-2"></i>PDF',className:"dropdown-item",exportOptions:{columns:[0,1,2,3,4]}}]}],order:[[0,"desc"]],pageLength:10,lengthMenu:[[10,25,50,-1],[10,25,50,"الكل"]],language:{search:"بحث:",searchPlaceholder:"بحث في السجلات...",lengthMenu:"عرض _MENU_ سجلات",info:"عرض _START_ إلى _END_ من _TOTAL_ سجل",paginate:{first:"الأول",last:"الأخير",next:"التالي",previous:"السابق"}}});$("#security-filters").on("submit",function(a){a.preventDefault(),n.draw()}),function(){var o=$(".flatpickr");if(o.length&&typeof $.fn.flatpickr=="function"){var t={dateFormat:"Y-m-d"};try{window.flatpickr&&window.flatpickr.l10ns&&window.flatpickr.l10ns.ar&&(t.locale=window.flatpickr.l10ns.ar)}catch{}try{o.flatpickr(t)}catch(e){console&&console.warn&&console.warn("Flatpickr init skipped:",e)}}}(),$('[data-bs-toggle="tooltip"]').tooltip(),$(document).on("click",".delete-log",function(a){a.preventDefault();var o=$(this),t=o.closest("form").attr("action");confirm("هل أنت متأكد من حذف هذا السجل؟")&&$.ajax({url:t,type:"POST",data:{_token:c,_method:"DELETE"},beforeSend:function(){o.prop("disabled",!0).html('<i class="logos-icon ti tabler-loader ti-spin"></i>')},success:function(e){e.success?(typeof n<"u"&&n.draw(!1),toastr.success(e.message||"تم حذف السجل بنجاح")):toastr.error(e.message||"حدث خطأ أثناء حذف السجل")},error:function(e){var s=e.responseJSON?e.responseJSON.message:"حدث خطأ أثناء حذف السجل";toastr.error(s)},complete:function(){o.prop("disabled",!1).html('<i class="logos-icon ti tabler-trash"></i>')}})}),$(document).on("click",".view-details",function(a){a.preventDefault();var o=$(this).data("id");$.ajax({url:`/dashboard/security/logs/${o}`,type:"GET",success:function(t){if(t.success){var e=`
                        <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">تفاصيل السجل</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>نوع الحدث:</strong> ${t.data.event_type}</p>
                                                <p><strong>الوقت:</strong> ${t.data.created_at}</p>
                                                <p><strong>عنوان IP:</strong> ${t.data.ip_address}</p>
                                                <p><strong>متصفح المستخدم:</strong> ${t.data.user_agent}</p>
                                                <p><strong>المسار:</strong> ${t.data.route}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>الوصف:</strong> ${t.data.description}</p>
                                                <p><strong>مستوى الخطورة:</strong> ${t.data.severity}</p>
                                                <p><strong>بيانات الطلب:</strong> <pre>${JSON.stringify(JSON.parse(t.data.request_data||"{}"),null,2)}</pre></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;$("body").append(e);var s=new bootstrap.Modal(document.getElementById("logDetailsModal"));s.show(),document.getElementById("logDetailsModal").addEventListener("hidden.bs.modal",function(){$(this).remove()})}else toastr.error(t.message)},error:function(t){var e=t.responseJSON?t.responseJSON.message:"حدث خطأ أثناء عرض التفاصيل";toastr.error(e)}})}),document.querySelectorAll(".dropdown-item").forEach(function(a){a.querySelector(".ti-info-circle")&&a.addEventListener("click",function(o){o.preventDefault();var t=this.closest("tr"),e=t.querySelector("td:first-child .badge").textContent,s=t.querySelector("td:nth-child(3)").textContent,r=t.querySelector("td:nth-child(4)")?t.querySelector("td:nth-child(4)").textContent:"لا توجد توصيات";$("#securityDetailModal").modal("show"),$("#securityDetailTitle").text("تفاصيل المشكلة: "+e),$("#securityDetailDescription").text(s),$("#securityDetailRecommendation").text(r)})})});
