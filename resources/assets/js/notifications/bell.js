'use strict';

(function () {
  function renderList(notifs) {
    try {
      const listContainer = document.querySelector('.dropdown-notifications-list .list-group');
      if (!listContainer) return;
      listContainer.innerHTML = '';
      if (!Array.isArray(notifs) || notifs.length === 0) {
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action dropdown-notifications-item text-center py-4';
        li.innerHTML = '<div class="text-muted">لا توجد إشعارات جديدة</div>';
        listContainer.appendChild(li);
        return;
      }
      notifs.slice(0, 5).forEach(n => {
        const title = (n.data && (n.data.title || n.data.notification_title)) || 'إشعار';
        const message = (n.data && (n.data.body || n.data.message)) || '';
        const iconClass = n.data && n.data.icon_class ? n.data.icon_class : 'bg-primary';
        const icon = n.data && n.data.icon ? n.data.icon : 'ti tabler-bell';
        const createdAt = n.created_at || '';
        const li = document.createElement('li');
        li.className = 'list-group-item list-group-item-action dropdown-notifications-item';
        li.innerHTML = `
          <div class="d-flex gap-2">
            <div class="flex-shrink-0">
              <div class="avatar">
                <span class="avatar-initial rounded-circle ${iconClass}">
                  <i class="${icon}"></i>
                </span>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1">${escapeHtml(title)}</h6>
              <p class="mb-0">${escapeHtml(message)}</p>
              <small class="text-muted">${escapeHtml(createdAt)}</small>
            </div>
          </div>
        `;
        listContainer.appendChild(li);
      });
    } catch (_) {}
  }

  function updateBadge(count) {
    try {
      const wrap = document.querySelector('.nav-item.dropdown-notifications .nav-link .position-relative');
      if (!wrap) return;
      let badge = wrap.querySelector('.notification-badge');
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'position-absolute top-0 start-100 translate-middle-x badge rounded-pill bg-danger notification-badge';
        badge.style.fontSize = '0.65rem';
        badge.style.transform = 'translate(-50%, -50%)';
        wrap.appendChild(badge);
      }
      badge.textContent = String(count);
      badge.classList.toggle('d-none', count <= 0);
      if (count <= 0) badge.textContent = '0';
    } catch (_) {}
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  async function fetchNotifications() {
    try {
      const res = await fetch('/dashboard/notifications/json', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if (!res.ok) return;
      const json = await res.json();
      const items = Array.isArray(json.data) ? json.data : [];
      const unreadCount = typeof json.unread_count === 'number' ? json.unread_count : items.filter(n => !n.read_at).length;
      updateBadge(unreadCount);
      // عرض أحدث غير المقروء أولاً، أو العناصر كما هي
      const display = items.filter(n => !n.read_at).concat(items.filter(n => !!n.read_at));
      renderList(display);
    } catch (_) {}
  }

  function bootstrap() {
    // Initial fetch and then poll
    fetchNotifications();
    setInterval(fetchNotifications, 15000); // كل 15 ثانية
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
