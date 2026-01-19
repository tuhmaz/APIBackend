document.addEventListener("DOMContentLoaded",function(){document.getElementById("dropzone-files")&&(new Dropzone("#dropzone-files",{url:"/dashboard/articles/upload-file",paramName:"file",maxFilesize:10,addRemoveLinks:!0,dictDefaultMessage:document.querySelector("#dropzone-files .dz-message").innerHTML,acceptedFiles:".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt",headers:{"X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},init:function(){this.on("sending",function(o,t,n){const r=document.getElementById("file_category").value;if(!r){this.removeFile(o),d("Please select a file category before uploading");return}n.append("file_category",r),n.append("article_id",document.querySelector("form").dataset.articleId),n.append("class_id",document.getElementById("class_id").value),n.append("subject_id",document.getElementById("subject_id").value),n.append("semester_id",document.getElementById("semester_id").value),n.append("country",new URLSearchParams(window.location.search).get("country")||"1")}),this.on("success",function(o,t){const n=document.createElement("tr");n.dataset.fileId=t.file.id,n.innerHTML=`
            <td>
              <i class="page-icon ti tabler-file me-2"></i>
              ${t.file.name}
            </td>
            <td>
              <span class="badge bg-label-${u(t.file.category)}">
                ${t.file.category}
              </span>
            </td>
            <td>${f(t.file.size)}</td>
            <td>
              <div class="d-flex gap-2">
                <a href="${t.file.path}"
                   class="btn btn-sm btn-label-primary"
                   target="_blank"
                   title="${window.translations.Download||"Download"}">
                  <i class="page-icon ti tabler-download"></i>
                </a>
                <button type="button"
                        class="btn btn-sm btn-label-danger delete-file"
                        data-file-id="${t.file.id}"
                        title="${window.translations.Delete||"Delete"}">
                  <i class="page-icon ti tabler-trash"></i>
                </button>
              </div>
            </td>
          `,document.getElementById("files-list").appendChild(n),this.removeFile(o)}),this.on("error",function(o,t){d(typeof t=="string"?t:t.error),this.removeFile(o)})}}),document.addEventListener("click",function(o){if(o.target.closest(".delete-file")){const n=o.target.closest(".delete-file").dataset.fileId,r=document.querySelector(`tr[data-file-id="${n}"]`);confirm(window.translations.DeleteConfirm||"Are you sure you want to delete this file?")&&fetch("/dashboard/articles/remove-file",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({file_id:n})}).then(l=>l.json()).then(l=>{l.success?r.remove():d(l.message||window.translations.DeleteError||"Failed to delete file")}).catch(l=>{console.error("Error:",l),d(window.translations.DeleteError||"Failed to delete file")})}}));const e=document.getElementById("meta_description");e&&(e.addEventListener("input",function(){const o=this.getAttribute("maxlength"),t=this.value.length,n=o-t;document.getElementById("meta_chars").textContent=n}),e.dispatchEvent(new Event("input")));const i=document.getElementById("use_title_for_meta"),a=document.getElementById("use_keywords_for_meta"),s=document.getElementById("title"),c=document.getElementById("keywords");i&&a&&e&&(i.addEventListener("change",function(){this.checked&&(a.checked=!1,e.value=s.value,e.dispatchEvent(new Event("input")))}),a.addEventListener("change",function(){this.checked&&(i.checked=!1,e.value=c.value,e.dispatchEvent(new Event("input")))}))});function d(e){Swal.fire({title:window.translations.Error||"Error",text:e,icon:"error",customClass:{confirmButton:"btn btn-primary"},buttonsStyling:!1})}function u(e){return{plans:"primary",papers:"info",tests:"warning",books:"success",records:"secondary"}[e]||"primary"}function f(e){if(e===0)return"0 Bytes";const i=1024,a=["Bytes","KB","MB","GB"],s=Math.floor(Math.log(e)/Math.log(i));return parseFloat((e/Math.pow(i,s)).toFixed(2))+" "+a[s]}
