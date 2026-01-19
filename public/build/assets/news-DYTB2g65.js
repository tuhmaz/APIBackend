$(function(){var o=$(".datatables-news");if(tinymce.init({selector:"#content",plugins:"advlist autolink lists link image charmap preview anchor pagebreak",toolbar_mode:"floating",height:400}),o.length)var c=o.DataTable({processing:!0,serverSide:!0,ajax:{url:'{{ route("dashboard.news.data") }}?country={{ $currentCountry }}',type:"GET"},columns:[{data:"image",render:function(e){return`<div class="avatar"><img src="${e}" class="rounded" onerror="this.src='{{ asset('assets/img/illustrations/default_news_image.jpg') }}'"></div>`}},{data:"title",render:function(e,s,t){return`<div class="d-flex flex-column">
                          <span class="fw-semibold">${e}</span>
                          <small class="text-muted">${t.meta_description||""}</small>
                      </div>`}},{data:"category.name",render:function(e){return`<span class="badge bg-label-primary">${e}</span>`}},{data:"is_active",render:function(e,s,t){return`<div class="form-check form-switch d-flex justify-content-center">
                          <input type="checkbox" class="form-check-input toggle-status" ${e?"checked":""}
                              data-id="${t.id}" data-url="/dashboard/news/${t.id}/toggle-status"
                              style="width: 40px; height: 20px; cursor: pointer;">
                          <label class="form-check-label ms-2" style="cursor: pointer;">
                              <span class="badge ${e?"bg-success":"bg-secondary"}">
                                  ${e?'{{ __("Active") }}':'{{ __("Inactive") }}'}
                              </span>
                          </label>
                      </div>`}},{data:"is_featured",render:function(e,s,t){return`<div class="form-check form-switch d-flex justify-content-center">
                          <input type="checkbox" class="form-check-input toggle-featured" ${e?"checked":""}
                              data-id="${t.id}" data-url="/dashboard/news/${t.id}/toggle-featured"
                              style="width: 40px; height: 20px; cursor: pointer;">
                          <label class="form-check-label ms-2" style="cursor: pointer;">
                              <span class="badge ${e?"bg-warning":"bg-secondary"}">
                                  ${e?'{{ __("Featured") }}':'{{ __("Normal") }}'}
                              </span>
                          </label>
                      </div>`}},{data:"views",render:function(e){return`<span class="badge bg-label-info">${e}</span>`}},{data:"created_at",render:function(e){return`<span class="text-muted"><i class="page-icon ti tabler-calendar me-1"></i>${e}</span>`}},{data:"id",render:function(e,s,t){return`<div class="d-flex gap-2">
                          <a href="/dashboard/news/${e}/edit?country={{ $currentCountry }}"
                             class="btn btn-icon btn-label-primary btn-sm"
                             data-bs-toggle="tooltip"
                             title="{{ __('Edit') }}">
                              <i class="page-icon ti tabler-edit"></i>
                          </a>
                          <button type="button"
                                  class="btn btn-icon btn-label-danger btn-sm delete-record"
                                  data-id="${e}"
                                  data-country="{{ $currentCountry }}"
                                  data-bs-toggle="tooltip"
                                  title="{{ __('Delete') }}">
                              <i class="page-icon ti tabler-trash"></i>
                          </button>
                      </div>`}}],order:[[6,"desc"]],dom:'<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',language:{url:'{{ asset("assets/vendor/libs/datatables/i18n/" . app()->getLocale() . ".json") }}'}});$(document).on("click",".delete-record",function(){var e=$(this).data("id"),s=$(this).data("country"),t=$('meta[name="csrf-token"]').attr("content");Swal.fire({title:'{{ __("Are you sure?") }}',text:`{{ __("You won't be able to revert this!") }}`,icon:"warning",showCancelButton:!0,confirmButtonText:'{{ __("Yes, delete it!") }}',customClass:{confirmButton:"btn btn-danger me-3",cancelButton:"btn btn-label-secondary"},buttonsStyling:!1}).then(function(a){a.value&&$.ajax({url:"/dashboard/news/"+e,type:"DELETE",headers:{"X-CSRF-TOKEN":t},data:{country:s},success:function(r){c.ajax.reload(),Swal.fire({icon:"success",title:'{{ __("Deleted!") }}',text:r.message,customClass:{confirmButton:"btn btn-success"}})},error:function(r){var n;Swal.fire({icon:"error",title:'{{ __("Error!") }}',text:((n=r.responseJSON)==null?void 0:n.message)||'{{ __("Something went wrong!") }}',customClass:{confirmButton:"btn btn-danger"}})}})})}),$(document).on("change",".toggle-status",function(){var e=$(this),s=e.data("url");$.ajax({url:s,type:"PATCH",data:{_token:"{{ csrf_token() }}"},success:function(t){var a=e.siblings("label").find(".badge");e.is(":checked")?(a.removeClass("bg-secondary").addClass("bg-success"),a.text('{{ __("Active") }}')):(a.removeClass("bg-success").addClass("bg-secondary"),a.text('{{ __("Inactive") }}'))},error:function(t){var a;e.prop("checked",!e.is(":checked")),Swal.fire({icon:"error",title:'{{ __("Error!") }}',text:((a=t.responseJSON)==null?void 0:a.message)||'{{ __("Something went wrong!") }}',customClass:{confirmButton:"btn btn-danger"}})}})}),$(document).on("change",".toggle-featured",function(){var e=$(this),s=e.data("url"),t=$('meta[name="csrf-token"]').attr("content"),a=e.is(":checked");$.ajax({url:s,type:"PATCH",headers:{"X-CSRF-TOKEN":t},beforeSend:function(){e.prop("disabled",!0)},success:function(r){var n=e.siblings("label").find(".badge");a?(n.removeClass("bg-secondary").addClass("bg-warning"),n.text('{{ __("Featured") }}')):(n.removeClass("bg-warning").addClass("bg-secondary"),n.text('{{ __("Normal") }}'))},error:function(r){var n;e.prop("checked",!a),Swal.fire({icon:"error",title:'{{ __("Error!") }}',text:((n=r.responseJSON)==null?void 0:n.message)||'{{ __("Something went wrong!") }}',customClass:{confirmButton:"btn btn-danger"}})},complete:function(){e.prop("disabled",!1)}})});var i=[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));i.map(function(e){return new bootstrap.Tooltip(e)})});
