(function(){function l(a){try{const e=document.querySelector(".dropdown-notifications-list .list-group");if(!e)return;if(e.innerHTML="",!Array.isArray(a)||a.length===0){const t=document.createElement("li");t.className="list-group-item list-group-item-action dropdown-notifications-item text-center py-4",t.innerHTML='<div class="text-muted">لا توجد إشعارات جديدة</div>',e.appendChild(t);return}a.slice(0,5).forEach(t=>{const s=t.data&&(t.data.title||t.data.notification_title)||"إشعار",o=t.data&&(t.data.body||t.data.message)||"",i=t.data&&t.data.icon_class?t.data.icon_class:"bg-primary",u=t.data&&t.data.icon?t.data.icon:"ti tabler-bell",m=t.created_at||"",r=document.createElement("li");r.className="list-group-item list-group-item-action dropdown-notifications-item",r.innerHTML=`
          <div class="d-flex gap-2">
            <div class="flex-shrink-0">
              <div class="avatar">
                <span class="avatar-initial rounded-circle ${i}">
                  <i class="${u}"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1">${n(s)}</h6>
              <p class="mb-0">${n(o)}</p>
              <small class="text-muted">${n(m)}</small>
            </div>
          </div>
        `,e.appendChild(r)})}catch{}}function p(a){try{const e=document.querySelector(".nav-item.dropdown-notifications .nav-link .position-relative");if(!e)return;let t=e.querySelector(".notification-badge");t||(t=document.createElement("span"),t.className="position-absolute top-0 start-100 translate-middle-x badge rounded-pill bg-danger notification-badge",t.style.fontSize="0.65rem",t.style.transform="translate(-50%, -50%)",e.appendChild(t)),t.textContent=String(a),t.classList.toggle("d-none",a<=0),a<=0&&(t.textContent="0")}catch{}}function n(a){return String(a||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;")}async function c(){try{const a=await fetch("/dashboard/notifications/json",{headers:{"X-Requested-With":"XMLHttpRequest"},credentials:"same-origin"});if(!a.ok)return;const e=await a.json(),t=Array.isArray(e.data)?e.data:[],s=typeof e.unread_count=="number"?e.unread_count:t.filter(i=>!i.read_at).length;p(s);const o=t.filter(i=>!i.read_at).concat(t.filter(i=>!!i.read_at));l(o)}catch{}}function d(){c(),setInterval(c,15e3)}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",d):d()})();
