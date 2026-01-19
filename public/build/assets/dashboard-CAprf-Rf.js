document.addEventListener("DOMContentLoaded",function(){const n={series:[{name:window.translations.Articles||"Articles",data:articlesData},{name:window.translations.News||"News",data:newsData},{name:window.translations.Users||"Users",data:usersData}],chart:{height:350,type:"area",toolbar:{show:!1}},dataLabels:{enabled:!1},stroke:{curve:"smooth",width:2},colors:["#696cff","#03c3ec","#71dd37"],fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.7,opacityTo:.3,stops:[0,90,100]}},xaxis:{categories:dates,labels:{style:{colors:"#697a8d",fontSize:"13px"},rotate:-45,rotateAlways:!1},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:"#697a8d",fontSize:"13px"},formatter:function(t){return Math.floor(t)}},min:0,tickAmount:4},tooltip:{shared:!0,intersect:!1,y:{formatter:function(t){return t+" "+(window.translations.items||"items")}}},legend:{position:"top",horizontalAlign:"right",labels:{colors:"#697a8d"}},grid:{show:!0,borderColor:"#f0f0f0",strokeDashArray:4,padding:{top:0,right:0,bottom:0,left:0}}};document.querySelector("#growthChart")&&new ApexCharts(document.querySelector("#growthChart"),n).render(),document.querySelectorAll(".card-stats").forEach(t=>{t.addEventListener("mouseenter",function(){this.style.transform="translateY(-5px)",this.style.boxShadow="0 5px 15px rgba(0,0,0,0.1)"}),t.addEventListener("mouseleave",function(){this.style.transform="translateY(0)",this.style.boxShadow="none"})});function a(){fetch("/api/online-users").then(t=>t.json()).then(t=>{const e=document.querySelector(".online-users-list");e&&t.users&&t.users.length>0?e.innerHTML=t.users.map(s=>`
                        <div class="online-user-item d-flex align-items-center">
                            <div class="avatar-wrapper">
                                <img src="${s.profile_photo_path||"/assets/img/avatars/1.png"}"
                                     alt="${s.name}"
                                     class="avatar">
                                <span class="avatar-status bg-success"></span>
                            </div>
                            <div class="user-info">
                                <h6 class="user-name">${s.name}</h6>
                                <small class="user-status-text">${window.translations.Online||"Online"}</small>
                            </div>
                            <div class="user-actions">
                                <div class="dropdown">
                                    <button type="button" class="btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="page-icon ti tabler-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="javascript:void(0);">
                                            <i class="page-icon ti tabler-message me-1"></i>
                                            ${window.translations["Send Message"]||"Send Message"}
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);">
                                            <i class="page-icon ti tabler-user me-1"></i>
                                            ${window.translations["View Profile"]||"View Profile"}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join(""):e.innerHTML=`
                        <div class="text-center p-4">
                            <i class="page-icon ti tabler-users text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="mb-0">${window.translations["No online users at the moment"]||"No online users at the moment"}</p>
                        </div>
                    `}).catch(console.error)}setInterval(a,6e4),a();function o(){fetch("/api/dashboard/stats").then(t=>t.json()).then(t=>{document.getElementById("newsCount").textContent=t.newsCount,document.getElementById("articlesCount").textContent=t.articlesCount,document.getElementById("usersCount").textContent=t.usersCount}).catch(console.error)}setInterval(o,3e5)});
