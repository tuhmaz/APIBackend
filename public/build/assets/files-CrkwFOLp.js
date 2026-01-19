document.addEventListener("DOMContentLoaded",function(){new Dropzone("#dropzone-basic",{url:uploadUrl,paramName:"file",maxFilesize:10,maxFiles:10,acceptedFiles:".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx",addRemoveLinks:!0,headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},dictDefaultMessage:"قم بإسقاط الملفات هنا للتحميل",dictFallbackMessage:"المتصفح الخاص بك لا يدعم السحب والإفلات للملفات.",dictFileTooBig:"الملف كبير جداً ({{filesize}}ميجابايت). الحد الأقصى: {{maxFilesize}}ميجابايت.",dictInvalidFileType:"لا يمكنك تحميل ملفات من هذا النوع.",dictResponseError:"الخادم استجاب مع {{statusCode}}.",dictCancelUpload:"إلغاء التحميل",dictUploadCanceled:"تم إلغاء التحميل.",dictCancelUploadConfirmation:"هل أنت متأكد من إلغاء التحميل؟",dictRemoveFile:"حذف الملف",dictMaxFilesExceeded:"لا يمكنك تحميل المزيد من الملفات.",init:function(){this.on("success",function(t,n){if(n.success){const i=document.querySelector("#filesTable tbody"),s=c(n.file);i.insertBefore(s,i.firstChild),a("success","تم تحميل الملف بنجاح")}}),this.on("error",function(t,n){a("error",typeof n=="string"?n:"حدث خطأ أثناء تحميل الملف")})}}),document.addEventListener("click",function(t){if(t.target.closest(".delete-file")){t.preventDefault();const n=t.target.closest(".delete-file");n.dataset.id;const i=n.dataset.url;Swal.fire({title:"حذف الملف",text:"هل أنت متأكد من حذف هذا الملف؟",icon:"warning",showCancelButton:!0,confirmButtonText:"نعم، احذفه!",cancelButtonText:"إلغاء",customClass:{confirmButton:"btn btn-danger me-3",cancelButton:"btn btn-label-secondary"},buttonsStyling:!1}).then(s=>{s.isConfirmed&&fetch(i,{method:"DELETE",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content}}).then(o=>o.json()).then(o=>{if(o.success)n.closest("tr").remove(),a("success","تم حذف الملف بنجاح");else throw new Error(o.message)}).catch(o=>{a("error",o.message||"حدث خطأ أثناء حذف الملف")})})}});const e=document.querySelector("#countrySelect");e&&e.addEventListener("change",function(){window.location.href=`${indexUrl}?country=${this.value}`})});function c(e){const t=document.createElement("tr");return t.innerHTML=`
    <td>
      <div class="d-flex align-items-center">
        <i class="page-icon ti tabler-${l(e.extension)} text-primary me-2" style="font-size: 1.5rem;"></i>
        <div>
          <h6 class="mb-0">${e.original_name}</h6>
          <small class="text-muted">${r(e.size)}</small>
        </div>
      </div>
    </td>
    <td>
      <span class="badge bg-label-primary">${e.extension.toUpperCase()}</span>
    </td>
    <td>
      <span class="text-muted"><i class="page-icon ti tabler-calendar me-1"></i>${d(e.created_at)}</span>
    </td>
    <td>
      <div class="d-flex gap-2">
        <a href="${e.url}" class="btn btn-icon btn-label-primary btn-sm" download>
          <i class="page-icon ti tabler-download"></i>
        </a>
        <button type="button" class="btn btn-icon btn-label-danger btn-sm delete-file"
                data-id="${e.id}" data-url="${deleteUrl.replace(":id",e.id)}">
          <i class="page-icon ti tabler-trash"></i>
        </button>
      </div>
    </td>
  `,t}function l(e){return{pdf:"file-type-pdf",doc:"file-type-doc",docx:"file-type-doc",xls:"file-type-xls",xlsx:"file-type-xls",jpg:"photo",jpeg:"photo",png:"photo",gif:"photo"}[e.toLowerCase()]||"file"}function r(e,t=2){if(e===0)return"0 Bytes";const n=1024,i=t<0?0:t,s=["Bytes","KB","MB","GB","TB"],o=Math.floor(Math.log(e)/Math.log(n));return parseFloat((e/Math.pow(n,o)).toFixed(i))+" "+s[o]}function d(e){return new Date(e).toLocaleDateString("ar-EG",{year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"})}function a(e,t){Swal.fire({icon:e,title:e==="success"?"نجاح":"خطأ",text:t,showConfirmButton:!1,timer:3e3,customClass:{popup:"animated fadeInDown"}})}
