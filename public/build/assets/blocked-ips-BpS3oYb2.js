document.addEventListener("DOMContentLoaded",function(){let n=$(".datatables-blocked-ips");n.length&&n.DataTable({dom:'<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',displayLength:10,lengthMenu:[10,25,50,75,100],responsive:!0,order:[[2,"desc"]],columns:[{data:"ip_address",render:function(t){return`<div class="d-flex align-items-center">
                            <i class="bx bx-shield-x text-danger me-2"></i>
                            <span>${t}</span>
                        </div>`}},{data:null,render:function(t){return t.country_code?`<img src="${window.location.origin}/assets/img/flags/${t.country_code.toLowerCase()}.png"
                                     alt="${t.country_code}"
                                     class="me-1"
                                     style="width: 20px;">
                                ${t.city?t.city+", ":""}${t.country_code}`:window.translations.unknown||"Unknown"}},{data:"last_attempt",render:function(t){return`<div data-bs-toggle="tooltip" title="${t}">
                            ${moment(t).fromNow()}
                        </div>`}},{data:"attempts_count",render:function(t){return`<span class="badge bg-label-danger">${t}</span>`}},{data:"max_risk_score",render:function(t){return`<div class="d-flex align-items-center">
                            <div class="progress w-100" style="height: 8px;">
                                <div class="progress-bar bg-${t>=75?"danger":t>=50?"warning":"primary"}"
                                     role="progressbar"
                                     style="width: ${t}%"
                                     aria-valuenow="${t}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <span class="ms-2">${t}</span>
                        </div>`}},{data:"attack_type",render:function(t){const e=t||"unknown",o=e==="brute_force"?"danger":e==="sql_injection"?"warning":"info",i=e.split("_").map(s=>s.charAt(0).toUpperCase()+s.slice(1)).join(" ");return`<span class="text-truncate d-flex align-items-center">
                            <span class="badge bg-label-${o} ms-1">
                                ${i}
                            </span>
                        </span>`}},{data:"ip_address",orderable:!1,searchable:!1,render:function(t){return`<div class="d-inline-block">
                            <button type="button"
                                    class="btn btn-sm btn-icon"
                                    data-bs-toggle="tooltip"
                                    title="${window.translations.view_details||"View Details"}"
                                    onclick="viewIpDetails('${t}')">
                                <i class="bx bx-show text-primary"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-icon delete-record"
                                    data-bs-toggle="tooltip"
                                    title="${window.translations.unblock_ip||"Unblock IP"}"
                                    onclick="unblockIp('${t}')">
                                <i class="bx bx-shield-quarter text-success"></i>
                            </button>
                        </div>`}}]}).on("draw",function(){[].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function(e){return new bootstrap.Tooltip(e)})})});
