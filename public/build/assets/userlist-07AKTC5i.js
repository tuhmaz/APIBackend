$(function(){let t=[];$(".select2").select2({width:"100%"}),$.ajaxSetup({headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")}});const o=$("#users-table-body"),f=$("#pagination-links");let s;const m=300;function l(e=null){const n=$("#filterForm").data("users-url"),c=e||n;s&&clearTimeout(s),s=setTimeout(()=>{o.html(`
                <tr>
                    <td colspan="4" class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </td>
                </tr>
            `)},m),$.ajax({url:c,method:"GET",data:{role:$("#UserRole").val()||"",search:$("#UserSearch").val()||""}}).done(function(r){clearTimeout(s);const d=$(r),u=d.find("#users-table-body"),h=d.find("#pagination-links");u.length?o.html(u.html()):o.html(`
                    <tr>
                        <td colspan="4" class="text-center">
                            {{ __('No results found') }}
                        </td>
                    </tr>
                `),h.length&&f.html(h.html());const a=new URL(window.location);a.searchParams.set("role",$("#UserRole").val()||""),a.searchParams.set("search",$("#UserSearch").val()||""),window.history.pushState({},"",a)}).fail(function(r){clearTimeout(s),o.html(`
                <tr>
                    <td colspan="4" class="text-center text-danger p-3">
                        <i class="page-icon ti tabler-alert-circle me-1"></i>
                        ${r.status===0?$("#filterForm").data("network-error"):$("#filterForm").data("loading-error")}
                    </td>
                </tr>
            `)})}function p(e,n){let c;return function(...r){clearTimeout(c),c=setTimeout(function(){e.apply(this,r)},n)}}$("#UserRole").on("change",function(){l()}),$("#UserSearch").on("input",p(function(){l()},500)),$(document).on("click","#pagination-links a",function(e){e.preventDefault(),l($(this).attr("href"))}),$("#resetFiltersBtn").on("click",function(){$("#UserRole").val("").trigger("change"),$("#UserSearch").val(""),l()}),$(document).on("click",".delete-record",function(e){confirm($("#filterForm").data("delete-confirm"))||e.preventDefault()}),$(document).on("change","#select-all-users",function(){const e=$(this).prop("checked");$(".user-checkbox").prop("checked",e),i()}),$(document).on("change",".user-checkbox",function(){i();const e=$(".user-checkbox:checked").length===$(".user-checkbox").length;$("#select-all-users").prop("checked",e)});function i(){t=[],$(".user-checkbox:checked").each(function(){t.push($(this).val())}),t.length>0?$("#delete-selected").removeClass("d-none").text(`حذف المحدد (${t.length})`):$("#delete-selected").addClass("d-none")}$("#delete-selected").on("click",function(){t.length!==0&&confirm("هل أنت متأكد من رغبتك في حذف المستخدمين المحددين؟ هذا الإجراء لا يمكن التراجع عنه.")&&$.ajax({url:$("#filterForm").data("users-url")+"/bulk-delete",method:"POST",data:{user_ids:t},beforeSend:function(){$("#delete-selected").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الحذف...'),$("#delete-selected").prop("disabled",!0)}}).done(function(e){Swal.fire({title:"تم الحذف بنجاح!",text:e.message||"تم حذف المستخدمين المحددين بنجاح",icon:"success",confirmButtonText:"حسناً"}),l(),t=[],$("#delete-selected").addClass("d-none")}).fail(function(e){var n;Swal.fire({title:"خطأ!",text:((n=e.responseJSON)==null?void 0:n.message)||"حدث خطأ أثناء محاولة حذف المستخدمين",icon:"error",confirmButtonText:"حسناً"})}).always(function(){$("#delete-selected").html('<i class="page-icon ti tabler-trash me-1"></i>حذف المحدد'),$("#delete-selected").prop("disabled",!1)})})});
