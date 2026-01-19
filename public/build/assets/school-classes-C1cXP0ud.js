function t(a){return window.translations[a]||a}$(function(){$(".select2").select2(),$("#countrySelect").val()||$("#countrySelect").val("1").trigger("change"),$("#countrySelect").on("change",function(){$("#filterForm").submit()});let a=$(".datatables-classes").DataTable({ajax:{url:window.location.href,data:function(e){e.country_id=$("#countrySelect").val()}},columns:[{data:"DT_RowIndex",name:"DT_RowIndex",orderable:!1,searchable:!1},{data:"grade_name"},{data:"grade_level",render:function(e){return`<span class="badge bg-label-primary">${e}</span>`}},{data:"country_name"},{data:"subjects_count",render:function(e){return`<span class="badge bg-label-info">${e}</span>`}},{data:"created_at"},{data:null,orderable:!1,render:function(e,o,n){return`
                        <div class="d-inline-flex">
                            <a href="${n.show_url}"
                               class="btn btn-sm btn-icon btn-label-primary me-1"
                               data-bs-toggle="tooltip"
                               title="${t("View Details")}">
                                <i class="page-icon ti tabler-eye"></i>
                            </a>
                            <a href="${n.edit_url}"
                               class="btn btn-sm btn-icon btn-label-warning me-1"
                               data-bs-toggle="tooltip"
                               title="${t("Edit")}">
                                <i class="page-icon ti tabler-edit"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-label-danger delete-record"
                                    data-bs-toggle="tooltip"
                                    title="${t("Delete")}"
                                    data-url="${n.delete_url}">
                                <i class="page-icon ti tabler-trash"></i>
                            </button>
                        </div>
                    `}}],order:[[2,"asc"]],dom:'<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',language:{emptyTable:"لا توجد بيانات متاحة في الجدول",loadingRecords:"جارٍ التحميل...",processing:"جارٍ التحميل...",lengthMenu:"أظهر _MENU_ مدخلات",zeroRecords:"لم يعثر على أية سجلات",info:"إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",infoEmpty:"يعرض 0 إلى 0 من أصل 0 سجل",infoFiltered:"(منتقاة من مجموع _MAX_ مُدخل)",search:"ابحث:",paginate:{first:"الأول",previous:"السابق",next:"التالي",last:"الأخير"},aria:{sortAscending:": تفعيل لترتيب العمود تصاعدياً",sortDescending:": تفعيل لترتيب العمود تنازلياً"}}});$(".datatables-classes tbody").on("click",".delete-record",function(){let o=$(this).data("url");Swal.fire({title:t("Are you sure?"),text:t("You will not be able to recover this!"),icon:"warning",showCancelButton:!0,confirmButtonText:t("Yes, delete it!"),cancelButtonText:t("No, cancel!"),customClass:{confirmButton:"btn btn-danger me-3",cancelButton:"btn btn-label-secondary"},buttonsStyling:!1}).then(function(n){n.value&&$.ajax({url:o,type:"DELETE",data:{_token:$('meta[name="csrf-token"]').attr("content")},success:function(){a.ajax.reload(),Swal.fire({icon:"success",title:t("Deleted!"),text:t("Record has been deleted."),customClass:{confirmButton:"btn btn-success"}})},error:function(r){var l;Swal.fire({icon:"error",title:t("Error!"),text:((l=r.responseJSON)==null?void 0:l.message)||t("Something went wrong!"),customClass:{confirmButton:"btn btn-primary"}})}})})}),[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function(e){return new bootstrap.Tooltip(e)}),$(".delete-record").on("click",function(e){e.preventDefault();const o=$(this).closest("form");Swal.fire({title:t("Are you sure?"),text:t("You will not be able to recover this!"),icon:"warning",showCancelButton:!0,confirmButtonText:t("Yes, delete it!"),cancelButtonText:t("No, cancel!"),customClass:{confirmButton:"btn btn-primary me-3",cancelButton:"btn btn-label-secondary"},buttonsStyling:!1}).then(function(n){n.value&&o.submit()})})});
