'use strict';

/**
 * GLOBAL STATE
 */
window.currentUser = window.currentUser || {}; // يجب حقنه من Blade
window.currentChatUserId = null;
window.currentConversationId = null;
window.messagePoller = null;
window.lastMessageId = null;
window.blockUser = null;
window.unblockUser = null;
window.clearChat = null;

window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Default API routes for chat (can be overridden from Blade if needed)
const defaultChatRoutes = {
  conversations: '/api/dashboard/chat/conversations',
  messages: '/api/dashboard/chat/conversations/:id/messages',
  send: '/api/dashboard/chat/conversations/:id/messages',
  // aliases for existing code
  sendMessage: '/api/dashboard/chat/conversations/:id/messages',
  users: '/api/dashboard/chat/users',
  user: '/api/dashboard/chat/user/:id',
  unread: '/api/dashboard/chat/unread-counts',
  block: '/api/dashboard/chat/block/:id',
  unblock: '/api/dashboard/chat/unblock/:id',
  blockStatus: '/api/dashboard/chat/block-status/:id',
  clear: '/api/dashboard/chat/conversations/:id/clear',
  // web route for hiding a private conversation from my list
  hide: '/dashboard/chat/hide/:id',
  publicConversation: '/api/dashboard/chat/conversations/public',
  // alias used in some parts
  public: '/api/dashboard/chat/conversations/public',
  privateConversation: '/api/dashboard/chat/conversations/private',
  // alias used by createPrivateChat()
  createPrivate: '/api/dashboard/chat/conversations/private'
};
// Merge any pre-injected routes from Blade to avoid losing defaults
window.chatRoutes = { ...defaultChatRoutes, ...(window.chatRoutes || {}) };

// Contacts pagination/search state
window.contactsState = {
  page: 1,
  perPage: 50,
  hasMore: true,
  q: '',
  loading: false,
  onlineOnly: false
};

// Determine if a user is considered online based on multiple possible fields
function isUserOnline(u) {
  try {
    if (!u) return false;
    if (typeof u.is_online !== 'undefined') return !!u.is_online;
    if (typeof u.online !== 'undefined') return !!u.online;
    if (typeof u.status === 'string') {
      const s = u.status.toLowerCase();
      if (s === 'online' || s === 'active') return true;
    }
    // Fallback: last_activity within last 5 minutes
    if (u.last_activity) {
      const last = new Date(u.last_activity).getTime();
      if (!isNaN(last)) {
        return (Date.now() - last) <= 5 * 60 * 1000;
      }
    }
  } catch (_) {}
  return false;
}

// Helper: check if current user is admin (supports multiple shapes)
function isCurrentUserAdmin() {
  try {
    // Prefer explicit injected flags
    if (typeof window.isSuperAdmin === 'boolean') return !!window.isSuperAdmin;
    if (Array.isArray(window.currentUserRoles)) {
      const has = window.currentUserRoles.some(r => (r || '').toLowerCase() === 'super_admin');
      if (has) return true;
    }
    const u = window.currentUser || {};
    if (typeof u.role === 'string') return u.role.toLowerCase() === 'super_admin';
    if (u.role && typeof u.role.name === 'string') return u.role.name.toLowerCase() === 'super_admin';
    if (Array.isArray(u.roles)) return u.roles.some(r => (r?.name || '').toLowerCase() === 'super_admin');
    if (u.role_id && (u.role_id === 1 || u.role_id === '1')) return true; // fallback by id
  } catch (_) {}
  return false;
}

/**
 * UI HELPERS
 */
function scrollToBottom() {
  const container = document.querySelector('.chat-history-body');
  if (container) container.scrollTop = container.scrollHeight;
}

// Alert Types
const ALERT_TYPES = {
    success: 'success',
    danger: 'danger',
    warning: 'warning',
    info: 'info',
    primary: 'primary',
    secondary: 'secondary',
    dark: 'dark'
};

// Alert Icons
const ALERT_ICONS = {
    success: 'ti tabler-check',
    danger: 'ti tabler-ban',
    warning: 'ti tabler-bell',
    info: 'ti tabler-info-circle',
    primary: 'ti tabler-user',
    secondary: 'ti tabler-bookmark',
    dark: 'ti tabler-at'
};

// Show Alert with Icon
function showAlert(type, message, options = {}) {
    const alertContainer = document.getElementById('chat-alerts') || createAlertContainer();

    const alert = document.createElement('div');
    alert.className = `alert alert-solid-${type} d-flex align-items-center alert-dismissible mb-0`;

    // Add icon
    const iconSpan = document.createElement('span');
    iconSpan.className = 'alert-icon rounded';
    const icon = document.createElement('i');
    icon.className = `icon-base ${ALERT_ICONS[type] || ALERT_ICONS.success} icon-md`;
    iconSpan.appendChild(icon);

    // Add message
    const messageDiv = document.createElement('div');
    messageDiv.className = 'd-flex flex-column ps-1';
    messageDiv.innerHTML = `
        <h5 class="alert-heading mb-2">${options.title || 'تنبيه'}</h5>
        <p class="mb-0">${message}</p>
    `;

    // Add close button
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close';
    closeButton.setAttribute('data-bs-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');

    // Append all elements
    alert.appendChild(iconSpan);
    alert.appendChild(messageDiv);
    alert.appendChild(closeButton);
    alertContainer.appendChild(alert);

    // Auto remove after timeout
    if (options.timeout !== false) {
        setTimeout(() => {
            alert.remove();
        }, options.timeout || 5000);
    }
}

// Create alert container if not exists
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'chat-alerts';
    container.className = 'position-fixed top-0 end-0 p-3';
    // Ensure alerts appear above navbars/sidebars/modals
    // Bootstrap modal z-index is ~1050; use a higher value
    container.style.zIndex = '2000';
    document.body.appendChild(container);
  return container;
}

// ===== Notifications & Unread Helpers =====
function isSoundEnabled() {
  return localStorage.getItem('chat_sound_enabled') !== '0'; // افتراضي: مفعّل
}

function setSoundEnabled(enabled) {
  localStorage.setItem('chat_sound_enabled', enabled ? '1' : '0');
}

function playNotificationSound() {
  try {
    if (!isSoundEnabled()) return;
    let el = document.getElementById('chat-sound');
    if (!el) {
      el = document.createElement('audio');
      el.id = 'chat-sound';
      el.src = '/sounds/chat.mp3'; // غيّر المسار حسب ملف الصوت لديك
      el.preload = 'auto';
      el.style.display = 'none';
      document.body.appendChild(el);
    }
    if (el && typeof el.play === 'function') el.play().catch(() => {});
  } catch (_) {}
}

function ensureNotificationPermission() {
  try {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
      Notification.requestPermission();
    }
  } catch (_) {}
}

function notifyBrowser(user, message) {
  try {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') {
      new Notification(`رسالة من ${user?.name || 'مستخدم'}`, {
        body: message?.body || '',
        icon: user?.avatar || '/assets/img/avatars/4.png'
      });
    }
  } catch (_) {}
}

function showChatToast(user, text, conversationId) {
  if (!window.Swal || !window.Swal.fire) return;
  window.Swal.fire({
    toast: true,
    position: 'bottom-start',
    icon: 'info',
    title: `رسالة جديدة من ${user?.name || 'مستخدم'}`,
    text: text || '',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: toast => {
      toast.addEventListener('click', () => {
        if (typeof openConversation === 'function' && conversationId) {
          openConversation(conversationId);
        }
      });
    }
  });
}

function incrementUnreadCount(conversationId) {
  const badge = document.querySelector(`#chat-badge-${conversationId}`);
  if (!badge) return;
  const current = parseInt(badge.textContent) || 0;
  badge.textContent = current + 1;
  badge.classList.remove('d-none');
  const bell = document.querySelector(`#chat-bell-${conversationId}`);
  if (bell) bell.classList.remove('d-none');
}

function resetUnreadCount(conversationId) {
  const badge = document.querySelector(`#chat-badge-${conversationId}`);
  if (!badge) return;
  badge.textContent = '0';
  badge.classList.add('d-none');
  const bell = document.querySelector(`#chat-bell-${conversationId}`);
  if (bell) bell.classList.add('d-none');
}

function onNewMessage(message, conversationId) {
  const isActive = (conversationId === window.currentConversationId);
  if (!isActive) {
    const sender = message.sender || message.user;
    incrementUnreadCount(conversationId);
    showChatToast(sender, message.body, conversationId);
    notifyBrowser(sender, message);
    playNotificationSound();
  }
  if (isActive) {
    appendMessageToChat(message);
  }
}

// ===== End Notifications & Unread Helpers =====

// Ask for Notification permission on load
(function () {
  try {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', ensureNotificationPermission);
    } else {
      ensureNotificationPermission();
    }
  } catch (_) {}
})();

// Simple debounce helper for input events
function debounce(fn, delay = 300) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(null, args), delay);
  };
}

// Ensure unread badge and bell exist for the pinned public chat list item
function ensurePublicBadgeBell(publicId) {
  try {
    if (!publicId) return;
    const li = document.getElementById('public-chat-li');
    if (!li) return;
    // Avoid duplicates
    let badge = document.getElementById(`chat-badge-${publicId}`);
    let bell = document.getElementById(`chat-bell-${publicId}`);
    const titleContainer = li.querySelector('.flex-grow-1') || li;
    if (!badge) {
      badge = document.createElement('span');
      badge.id = `chat-badge-${publicId}`;
      badge.className = 'badge rounded-pill bg-danger text-uppercase ms-2 d-none';
      badge.textContent = '0';
      titleContainer.appendChild(badge);
    }
    if (!bell) {
      bell = document.createElement('i');
      bell.id = `chat-bell-${publicId}`;
      bell.className = 'icon-base ti tabler-bell icon-16px text-warning ms-2 d-none';
      bell.title = 'رسائل غير مقروءة';
      titleContainer.appendChild(bell);
    }
  } catch (_) {}
}

// ===== Mute Notifications Button =====
function updateMuteToggleUI() {
  const btn = document.getElementById('chat-mute-toggle');
  if (!btn) return;
  const muted = !isSoundEnabled();
  // Toggle icon and text gracefully
  const icon = btn.querySelector('i');
  const label = btn.querySelector('.label') || btn; // fallback to button text
  if (icon) {
    icon.classList.toggle('bx-bell', !muted);
    icon.classList.toggle('bx-bell-off', muted);
  }
  const txt = muted ? 'تفعيل الإشعارات الصوتية' : 'كتم الإشعارات الصوتية';
  if (label === btn) {
    btn.textContent = txt;
  } else {
    label.textContent = txt;
  }
  btn.setAttribute('aria-pressed', muted ? 'true' : 'false');
}

function bindMuteToggle() {
  const btn = document.getElementById('chat-mute-toggle');
  if (!btn) return;
  updateMuteToggleUI();
  btn.addEventListener('click', () => {
    const newEnabled = !isSoundEnabled();
    setSoundEnabled(newEnabled); // toggle
    updateMuteToggleUI();
    if (window.Swal && window.Swal.fire) {
      window.Swal.fire({
        toast: true,
        position: 'bottom-start',
        icon: 'success',
        title: newEnabled ? 'تم تفعيل الإشعارات الصوتية' : 'تم كتم الإشعارات الصوتية',
        showConfirmButton: false,
        timer: 1800
      });
    } else {
      showAlert(ALERT_TYPES.info, newEnabled ? 'تم تفعيل الإشعارات الصوتية' : 'تم كتم الإشعارات الصوتية');
    }
  });
}

// Bind after DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindMuteToggle);
} else {
  bindMuteToggle();
}
// ===== End Mute Notifications Button =====

// ===== Unread Counts Polling (for other conversations) =====
function updateUnreadUI(unreadMap) {
  if (!unreadMap) return;
  Object.keys(unreadMap).forEach(id => {
    const count = parseInt(unreadMap[id]) || 0;
    const badge = document.querySelector(`#chat-badge-${id}`);
    const bell = document.querySelector(`#chat-bell-${id}`);
    if (badge) {
      badge.textContent = count;
      badge.classList.toggle('d-none', count === 0);
    }
    if (bell) {
      bell.classList.toggle('d-none', count === 0);
    }
  });
}

function startUnreadCountsPoller() {
  try {
    if (window.unreadCountsPoller) clearInterval(window.unreadCountsPoller);
    const poll = () => {
      const current = window.currentConversationId ? `?current=${encodeURIComponent(window.currentConversationId)}` : '';
      fetch(`${window.chatRoutes.unread}${current}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(r => {
          if (r.status === 404) {
            clearInterval(window.unreadCountsPoller);
            window.unreadCountsPoller = null;
            console.info('Unread counts endpoint not found; stopping poller.');
            return null;
          }
          return r.ok ? r.json() : null;
        })
        .then(data => {
          if (!data) return;
          updateUnreadUI(data);
        })
        .catch(() => {});
    };
    poll();
    window.unreadCountsPoller = setInterval(poll, 12000); // كل 12 ثانية
  } catch (_) {}
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', startUnreadCountsPoller);
} else {
  startUnreadCountsPoller();
}
// ===== End Unread Counts Polling =====

// Specific chat alerts
function showSendMessageSuccess() {
    showAlert(ALERT_TYPES.success, 'تم إرسال الرسالة بنجاح', {
        title: 'تم الإرسال'
    });
}

function showSendMessageError(error) {
    showAlert(ALERT_TYPES.danger, error || 'حدث خطأ أثناء إرسال الرسالة', {
        title: 'خطأ'
    });
}

function showBlockSuccess(userId) {
    showAlert(ALERT_TYPES.success, 'تم حظر المستخدم بنجاح', {
        title: 'تم الحظر'
    });
}

function showUnblockSuccess(userId) {
    showAlert(ALERT_TYPES.success, 'تم فك حظر المستخدم بنجاح', {
        title: 'تم فك الحظر'
    });
}

function showReportSuccess() {
    showAlert(ALERT_TYPES.success, 'تم إرسال الإبلاغ بنجاح', {
        title: 'تم الإبلاغ'
    });
}

function showReportError() {
    showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء إرسال الإبلاغ', {
        title: 'خطأ'
    });
}



/**
 * SIDEBAR & CONTACT INFO
 */
function updateSidebarContact(user) {
  // الاسم
  document.getElementById('sidebar-contact-name').textContent = user.name || '---';
  // الدور/الوصف
  document.getElementById('sidebar-contact-role').textContent = user.role || '';
  // البريد
  document.getElementById('sidebar-contact-email').textContent = user.email || '';
  // نبذة مختصرة
  document.getElementById('sidebar-contact-about').textContent = user.about || 'لا توجد تفاصيل...';

  // الحالة: متصل أو غير متصل
  const statusText = user.is_online ? 'متصل' : 'غير متصل';
  const statusColor = user.is_online ? '#16c784' : '#aaa';
  document.getElementById('sidebar-contact-status').textContent = statusText;
  document.getElementById('sidebar-contact-status').style.color = statusColor;

  // الصورة في السايدبار
  const sidebarAvatarImg = document.getElementById('active-chat-sidebar-avatar')?.querySelector('img');
  if (sidebarAvatarImg) {
    sidebarAvatarImg.src = user.avatar || generateAvatar(user.name);
    sidebarAvatarImg.alt = user.name || 'User';
  }

  // الصورة في رأس الدردشة
  const headerAvatarWrapper = document.getElementById('active-chat-avatar-wrapper');
  if (headerAvatarWrapper) {
    const headerAvatarImg = headerAvatarWrapper.querySelector('img');
    if (headerAvatarImg) {
      headerAvatarImg.src = user.avatar || generateAvatar(user.name);
      headerAvatarImg.alt = user.name || 'User';
      headerAvatarWrapper.classList.remove('avatar-online', 'avatar-offline');
      headerAvatarWrapper.classList.add(user.is_online ? 'avatar-online' : 'avatar-offline');
    }
  }

  // تحديث الحالة في رأس الدردشة
  const chatStatusElement = document.getElementById('active-chat-status');
  if (chatStatusElement) {
    chatStatusElement.textContent = statusText;
    chatStatusElement.style.color = statusColor;
  }

  window.currentChatUserId = user.id;
  checkBlockStatus(user.id);
}

function generateAvatar(name) {
  // يمكنك تخصيص حجم الصورة ولون الخلفية/الخط
  name = encodeURIComponent(name || 'User');
  return `https://ui-avatars.com/api/?name=${name}&background=random&color=fff&size=128&rounded=true`;
}


/**
 * فتح بيانات العضو الجانبي
 */
function showContactSidebar(userId) {
  fetch(window.chatRoutes.user.replace(':id', userId), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin'
  })
    .then(r => r.json())
    .then(user => {
      updateSidebarContact(user);
    });
  document.getElementById('app-chat-sidebar-right')?.classList.add('show');
}

/**
 * تحديث حالة الأزرار (حظر/فك الحظر)
 */
function updateBlockButtons(data) {
    const blockBtn = document.getElementById('block-contact-btn');
    const unblockBtn = document.getElementById('unblock-contact-btn');
    const chatInput = document.getElementById('chat-message-input');
    const sendBtn = document.querySelector('#chat-message-form button[type="submit"]');

    // Debug log to check button elements
    console.log('Block buttons:', {
        blockBtn: blockBtn ? 'found' : 'not found',
        unblockBtn: unblockBtn ? 'found' : 'not found'
    });

    if (blockBtn && unblockBtn) {
        if (data.i_blocked) {
            // أنت قمت بالحظر
            console.log('Setting to blocked state');
            blockBtn.style.display = 'none';
            unblockBtn.style.display = 'block';
            if (chatInput) chatInput.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
        } else if (data.blocked_me) {
            // الطرف الآخر قام بحظرك
            console.log('Setting to blocked by other state');
            blockBtn.style.display = 'none';
            unblockBtn.style.display = 'none';
            if (chatInput) chatInput.disabled = true;
            if (sendBtn) sendBtn.disabled = true;
        } else {
            // لا يوجد حظر
            console.log('Setting to unblocked state');
            blockBtn.style.display = 'block';
            unblockBtn.style.display = 'none';
            if (chatInput) chatInput.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
        }
    }

    // إضافة إستماع للنقر على زر فك الحظر
    if (unblockBtn) {
        unblockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.currentChatUserId) {
                unblockUser(window.currentChatUserId);
            }
        });
    }
}

/**
 * فك حظر المستخدم
 */
function unblockUser(userId) {
    if (!userId) return;
    fetch(window.chatRoutes.unblock.replace(':id', userId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(() => {
            checkBlockStatus(userId);
            showAlert(ALERT_TYPES.success, 'تم فك حظر المستخدم بنجاح', {
                title: 'تم فك الحظر'
            });
        })
        .catch(error => {
            console.error('Error unblocking user:', error);
            showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء فك الحظر', {
                title: 'خطأ'
            });
        });
}

/**
 * حظر المستخدم
 */
function blockUser(userId) {
    if (!userId) return;

    fetch(window.chatRoutes.block.replace(':id', userId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(() => {
        checkBlockStatus(userId);
        showBlockSuccess(userId);
    })
    .catch(error => {
        console.error('Error blocking user:', error);
        showBlockError();
    });
}
function checkBlockStatus(userId) {
    if (!userId) return;
    fetch(window.chatRoutes.blockStatus.replace(':id', userId), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(data => {
            console.log('Block status response:', data); // Debug log
            updateBlockButtons(data);
        })
        .catch(error => {
            console.error('Error checking block status:', error);
        });
}

function getUserAvatar(user) {
  // إذا عنده صورة مخزنة في قاعدة البيانات أو في backend
  if (user.avatar) {
      return normalizeImageUrl(user.avatar);
  }
  // إذا لا يوجد صورة، استخدم ui-avatars.com
  const name = encodeURIComponent(user.name || 'User');
  return `https://ui-avatars.com/api/?name=${name}&background=16c784&color=fff&size=128&rounded=true`;
}

function normalizeImageUrl(url) {
  if (!url) return '/assets/img/avatars/default.png';
  // إذا url يبدأ بـ "/" فهو نسبي للـ domain الحالي
  if (url.startsWith('/')) {
      return window.location.origin + url;
  }
  // إذا url يبدأ بـ "http" فهو مطلق، استخدمه كما هو
  return url;
}


/**
 * CHAT LOGIC
 */
function openConversation(conversationId, conversation, otherUser = null) {
  window.currentConversationId = conversationId;
  document.getElementById('app-chat-conversation')?.classList.add('d-none');
  document.getElementById('app-chat-history')?.classList.remove('d-none');
  // Reset unread badge for this conversation
  resetUnreadCount(conversationId);

  // Toggle header actions (block/unblock/report/clear) depending on conversation type and role
  try {
    const isPublic = conversation && conversation.type === 'public';
    const blockBtn = document.getElementById('block-contact-btn');
    const unblockBtn = document.getElementById('unblock-contact-btn');
    const reportBtn = document.getElementById('report-contact');
    const clearBtn = document.getElementById('clear-chat');
    if (isPublic) {
      blockBtn?.classList.add('d-none');
      unblockBtn?.classList.add('d-none');
      reportBtn?.classList.add('d-none');
      if (clearBtn) {
        if (isCurrentUserAdmin()) {
          clearBtn.classList.remove('d-none', 'disabled');
          clearBtn.classList.remove('disabled');
          clearBtn.removeAttribute('aria-disabled');
          clearBtn.title = '';
        } else {
          // إخفاء مسح المحادثة لغير الأدمن في الدردشة العامة
          clearBtn.classList.add('d-none');
          clearBtn.classList.add('disabled');
          clearBtn.setAttribute('aria-disabled', 'true');
          clearBtn.title = 'مسح الدردشة العامة متاح للمشرف فقط';
        }
      }
    } else {
      // محادثة خاصة: أظهر الخيارات الافتراضية
      blockBtn?.classList.remove('d-none');
      reportBtn?.classList.remove('d-none');
      // زر فك الحظر يُدار حسب الحالة من checkBlockStatus
      if (clearBtn) {
        clearBtn.classList.remove('d-none', 'disabled');
        clearBtn.removeAttribute('aria-disabled');
        clearBtn.title = '';
      }
    }
  } catch (_) { }

  // الخطوة 1: استدعاء أولي (يضع اسم مؤقت)
  let userName = conversation.type === 'public'
    ? (conversation.title || 'Public Chat')
    : otherUser
      ? otherUser.name
      : 'Private Chat';

  document.getElementById('active-chat-name').textContent = userName;

  // اسم العضو الحالي للمتابعة
  window.currentChatUserId = otherUser ? otherUser.id : null;

  // الخطوة 2: إذا كان نوع المحادثة خاص (private) ويوجد معرف العضو الآخر، استدعي بياناته من السيرفر
  if (conversation.type !== 'public' && window.currentChatUserId) {
    fetch(window.chatRoutes.user.replace(':id', window.currentChatUserId), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(user => {
      // تحديث الصورة
      const headerAvatarImg = document.getElementById('active-chat-avatar');
      if (headerAvatarImg) {
        headerAvatarImg.src = getUserAvatar(user);
        headerAvatarImg.alt = user.name;
      }
      // تحديث الاسم (احتياطي)
      document.getElementById('active-chat-name').textContent = user.name || userName;
      // تحديث الحالة
      document.getElementById('active-chat-status').textContent = user.is_online ? 'متصل' : 'غير متصل';
    });
  } else {
    // إذا كان شات عام أو ليس هناك مستخدم، استخدم الصورة الافتراضية
    const headerAvatarImg = document.getElementById('active-chat-avatar');
    if (headerAvatarImg) {
      headerAvatarImg.src = otherUser?.avatar || '/assets/img/avatars/4.png';
      headerAvatarImg.alt = userName;
    }
    document.getElementById('active-chat-status').textContent = 'غير متصل';
  }

  // تفعيل حالة الحظر
  checkBlockStatus(window.currentChatUserId);

  // تحميل الرسائل
  fetch(window.chatRoutes.messages.replace(':id', conversationId), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin'
  })
    .then(r => r.json())
    .then(data => {
      renderMessages(data.messages || []);
      if (data.messages && data.messages.length) {
        window.lastMessageId = data.messages[data.messages.length - 1].id;
      } else {
        window.lastMessageId = null;
      }
      if (window.messagePoller) clearInterval(window.messagePoller);
      window.messagePoller = setInterval(() => {
        if (!window.currentConversationId) return;
        const url =
          window.chatRoutes.messages.replace(':id', window.currentConversationId) +
          (window.lastMessageId ? `?after=${window.lastMessageId}` : '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
          .then(r => r.json())
          .then(d => {
            if (d.messages && d.messages.length) {
              d.messages.forEach(m => onNewMessage(m, window.currentConversationId));
            }
          })
          .catch(() => { });
      }, 3000);
    });
}

function renderMessages(messages) {
  const list = document.getElementById('chat-history-list');
  if (!list) return;
  list.innerHTML = '';
  if (!messages.length) {
    list.innerHTML = `<li class="text-center my-4 text-muted">No messages yet</li>`;
    return;
  }
  messages.forEach(msg => appendMessageToChat(msg));
  scrollToBottom();
}

function appendMessageToChat(message) {
  const list = document.getElementById('chat-history-list');
  if (!list) return;
  // Skip if message already rendered
  if (document.querySelector(`#chat-history-list li[data-message-id='${message.id}']`)) return;

  const sender = message.sender || message.user || {};
  const senderId = (message.sender_id ?? message.user_id);
  const isMe = String(senderId) === String(window.currentUser?.id);
  const avatar = isMe
    ? (window.currentUser?.avatar || '/assets/img/avatars/4.png')
    : (sender.avatar || '/assets/img/avatars/4.png');
  const username = isMe ? (window.currentUser?.name || 'Me') : (sender.name || 'User');
  const userId = senderId;
  const sideClass = isMe ? 'chat-message-right' : '';

  let msgHtml = `
    <li class="chat-message ${sideClass}" data-message-id="${message.id}">
      <div class="d-flex overflow-hidden align-items-center">
        ${
          !isMe
            ? `<div class="user-avatar flex-shrink-0 me-4">
                <div class="avatar avatar-sm" data-user-id="${userId}" style="cursor:pointer;">
                  <img src="${avatar}" alt="Avatar" class="rounded-circle" />
                </div>
              </div>`
            : ''
        }
        <div class="chat-message-wrapper flex-grow-1">
          <div class="chat-message-text">
            <p class="mb-0">${message.body}</p>
          </div>
          <div class="${isMe ? 'text-end' : ''} text-body-secondary mt-1">
            <small class="chat-username" data-user-id="${userId}" style="cursor:pointer;">${username}</small>
          </div>
        </div>
        ${
          isMe
            ? `<div class="user-avatar flex-shrink-0 ms-4">
                <div class="avatar avatar-sm" data-user-id="${userId}" style="cursor:pointer;">
                  <img src="${avatar}" alt="Avatar" class="rounded-circle" />
                </div>
              </div>`
            : ''
        }
      </div>
    </li>
  `;
  list.insertAdjacentHTML('beforeend', msgHtml);
  scrollToBottom();
  if (message.id) window.lastMessageId = message.id;
}

/**
 * CONTACTS (users) LOAD/RENDER
 */
function loadContacts(append = false) {
  if (!window.chatRoutes?.users) return;
  if (window.contactsState.loading) return;
  window.contactsState.loading = true;
  const { page, perPage, q } = window.contactsState;
  const url = new URL(window.chatRoutes.users, window.location.origin);
  url.searchParams.set('page', page);
  url.searchParams.set('per_page', perPage);
  if (q && q.trim()) url.searchParams.set('q', q.trim());
  if (window.contactsState.onlineOnly) url.searchParams.set('online', '1');

  fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      let users = Array.isArray(data.users) ? data.users : [];
      // Apply client-side filter for online only if toggled
      try {
        if (window.contactsState.onlineOnly) {
          users = users.filter(u => isUserOnline(u));
        }
      } catch (_) {}
      // Sort online users first, then by name
      try {
        users = users.sort((a, b) => {
          const ao = isUserOnline(a) ? 1 : 0;
          const bo = isUserOnline(b) ? 1 : 0;
          if (bo !== ao) return bo - ao; // online first
          const an = (a?.name || '').toLowerCase();
          const bn = (b?.name || '').toLowerCase();
          return an.localeCompare(bn, undefined, { sensitivity: 'base' });
        });
      } catch (_) {}
      const pagination = data.pagination || {};
      renderContactsList(users, append);
      window.contactsState.hasMore = !!pagination.has_more;
      // Toggle fallback Load More button visibility
      try {
        const moreWrap = document.getElementById('contact-load-more');
        if (moreWrap) {
          moreWrap.classList.toggle('d-none', !window.contactsState.hasMore);
        }
      } catch (_) {}
    })
    .catch(() => {})
    .finally(() => { window.contactsState.loading = false; });
}

function renderContactsList(users, append = false) {
  const contactList = document.getElementById('contact-list');
  if (!contactList) return;
  if (!append) {
    contactList.querySelectorAll('.chat-contact-list-item:not(.chat-contact-list-item-title):not(.contact-list-item-0)')
      .forEach(e => e.remove());
  }
  if (!users.length && !append) {
    document.querySelector('.contact-list-item-0')?.classList.remove('d-none');
    return;
  }
  if (users.length) {
    document.querySelector('.contact-list-item-0')?.classList.add('d-none');
  }
  users.forEach(user => {
    let li = document.createElement('li');
    li.className = 'chat-contact-list-item';
    li.innerHTML = `
      <a href="#" class="d-flex align-items-center" data-user-id="${user.id}">
        <div class="flex-shrink-0 avatar ${isUserOnline(user) ? 'avatar-online' : 'avatar-offline'}">
          <img src="${getUserAvatar(user)}" alt="${user.name}" class="rounded-circle" />
        </div>
        <div class="chat-contact-info flex-grow-1 ms-4">
          <h6 class="chat-contact-name text-truncate m-0 fw-normal">${user.name}</h6>
          <small class="chat-contact-status text-truncate">${user.email || ''}</small>
        </div>
      </a>
    `;
    li.querySelector('a').addEventListener('click', function (e) {
      e.preventDefault();
      createPrivateChat(user.id, user.name);
    });
    contactList.appendChild(li);
  });
}


/**
 * CHATS LOAD/RENDER
 */
function loadConversations() {
  fetch(window.chatRoutes.conversations, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin'
  })
    .then(r => r.json())
    .then(data => renderChatList(data.conversations || []));
}

function renderChatList(conversations) {
  const chatList = document.getElementById('chat-list');
  chatList.querySelectorAll('.chat-contact-list-item:not(.chat-contact-list-item-title):not(.chat-list-item-0):not(#public-chat-li)').forEach(e => e.remove());
  if (!conversations.length) {
    document.querySelector('.chat-list-item-0').classList.remove('d-none');
    return;
  }
  document.querySelector('.chat-list-item-0').classList.add('d-none');
  conversations.forEach(conversation => {
    let otherUser = (conversation.type === 'private' && conversation.users) ? conversation.users.find(u => u.id !== window.currentUser.id) : null;
    let users = conversation.users?.map(u => u.name).join(', ');
    let lastMsg = (conversation.messages && conversation.messages[0]) ? conversation.messages[0].body : 'No messages yet';
    let li = document.createElement('li');
    li.className = 'chat-contact-list-item mb-1';
    const unread = conversation.unread_count || 0;
    li.innerHTML = `
      <a href="#" class="d-flex align-items-center w-100" data-conversation-id="${conversation.id}">
        <div class="flex-shrink-0 avatar avatar-online">
          <img src="${getUserAvatar(otherUser || {name: users})}" alt="Avatar" class="rounded-circle" />
        </div>
        <div class="chat-contact-info flex-grow-1 ms-4">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="chat-contact-name text-truncate m-0 fw-normal">${conversation.type === 'public' ? (conversation.title || 'Public Chat') : users}</h6>
            <div class="d-flex align-items-center">
              <small class="chat-contact-list-item-time"></small>
              <span id="chat-badge-${conversation.id}" class="badge bg-danger ms-2 ${unread ? '' : 'd-none'}">${unread || 0}</span>
              <i id="chat-bell-${conversation.id}" class="icon-base ti tabler-bell icon-16px text-warning ms-2 ${unread ? '' : 'd-none'}" title="رسائل غير مقروءة"></i>
            </div>
          </div>
          <small class="chat-contact-status text-truncate">${lastMsg}</small>
        </div>
      </a>
    `;
    const anchor = li.querySelector('a');
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      openConversation(conversation.id, conversation, otherUser);
    });

    // Add delete/hide button for private conversations only
    try {
      if (conversation.type === 'private') {
        const actionsDiv = li.querySelector('.chat-contact-info');
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-sm btn-icon btn-text-danger ms-2 chat-delete-btn';
        delBtn.title = 'إخفاء المحادثة';
        delBtn.innerHTML = '<i class="ti tabler-trash"></i>';
        actionsDiv?.appendChild(delBtn);
        delBtn.addEventListener('click', function (ev) {
          ev.stopPropagation();
          ev.preventDefault();
          hideConversation(conversation.id, li);
        });
      }
    } catch (_) {}
    chatList.appendChild(li);
  });
}

// Hide a private conversation from my list
function hideConversation(conversationId, listItemEl) {
  if (!conversationId) return;
  const exec = () => {
    fetch(window.chatRoutes.hide.replace(':id', conversationId), {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      credentials: 'same-origin'
    })
      .then(r => r.json())
      .then(data => {
        if (data && data.success) {
          if (listItemEl && listItemEl.parentNode) listItemEl.parentNode.removeChild(listItemEl);
          showAlert(ALERT_TYPES.success, 'تم إخفاء المحادثة من قائمتك.', { title: 'تم الإخفاء' });
        } else {
          showAlert(ALERT_TYPES.danger, (data && data.message) || 'تعذر إخفاء المحادثة', { title: 'خطأ' });
        }
      })
      .catch(() => {
        showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء الإخفاء', { title: 'خطأ' });
      });
  };
  if (window.Swal && window.Swal.fire) {
    window.Swal.fire({
      title: 'تأكيد الإخفاء',
      text: 'سيتم إخفاء هذه المحادثة من قائمتك فقط ولن تُحذف الرسائل للطرف الآخر.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'نعم، إخفاء',
      cancelButtonText: 'إلغاء',
      customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-outline-secondary ms-1' }
    }).then(res => { if (res.isConfirmed) exec(); });
  } else {
    if (confirm('سيتم إخفاء هذه المحادثة من قائمتك فقط. متابعة؟')) exec();
  }
}





/**
 * إنشاء محادثة خاصة
 */
function createPrivateChat(userId, userName) {
  fetch(window.chatRoutes.createPrivate, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
    },
    body: JSON.stringify({ user_id: userId })
  })
    .then(r => r.json())
    .then(data => {
      if (data.conversation_id) {
        openConversation(data.conversation_id, { id: data.conversation_id, users: [{ id: userId, name: userName }] }, { id: userId, name: userName });
      }
    });
}

/**
 * DOM EVENTS
 */
document.addEventListener('DOMContentLoaded', function () {
  if (window.chatRoutes && window.chatRoutes.conversations) {
    loadConversations();
  }
  if (window.chatRoutes && window.chatRoutes.users) {
    loadContacts();
  }

  // Contacts search (debounced)
  try {
    const searchInput = document.querySelector('#app-chat-contacts .chat-search-input') || document.querySelector('.chat-search-input');
    if (searchInput) {
      const onSearch = debounce(() => {
        const val = searchInput.value || '';
        window.contactsState.q = val;
        window.contactsState.page = 1;
        window.contactsState.hasMore = true;
        loadContacts(false);
      }, 350);
      searchInput.addEventListener('input', onSearch);
    }
  } catch (_) {}

  // Online-only filter toggle
  try {
    const chk = document.getElementById('contacts-online-only');
    if (chk) {
      chk.addEventListener('change', () => {
        window.contactsState.onlineOnly = !!chk.checked;
        window.contactsState.page = 1;
        window.contactsState.hasMore = true;
        loadContacts(false);
      });
    }
  } catch (_) {}

  // (Alphabet jump bar removed)

  // Infinite scroll on contacts list
  try {
    const sidebar = document.querySelector('#app-chat-contacts .sidebar-body');
    if (sidebar) {
      sidebar.addEventListener('scroll', () => {
        const nearBottom = sidebar.scrollTop + sidebar.clientHeight >= sidebar.scrollHeight - 200;
        if (nearBottom && window.contactsState.hasMore && !window.contactsState.loading) {
          window.contactsState.page += 1;
          loadContacts(true);
        }
      });
    }
  } catch (_) {}

  // Load More fallback button
  try {
    const btn = document.getElementById('contact-load-more-btn');
    if (btn) {
      btn.addEventListener('click', () => {
        if (window.contactsState.hasMore && !window.contactsState.loading) {
          window.contactsState.page += 1;
          loadContacts(true);
        }
      });
    }
  } catch (_) {}

  // Open pinned public chat when clicked
  const publicLi = document.getElementById('public-chat-li');
  if (publicLi && window.chatRoutes?.public) {
    publicLi.addEventListener('click', async function (e) {
      e.preventDefault();
      try {
        let id = window.PUBLIC_CHAT_ID;
        let title = 'الدردشة العامة';
        if (!id) {
          const res = await fetch(window.chatRoutes.public, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
          const d = await res.json();
          if (d?.id) {
            id = d.id;
            title = d.title || title;
            window.PUBLIC_CHAT_ID = id;
            publicLi.dataset.conversationId = id;
            ensurePublicBadgeBell(id);
          }
        }
        if (id) {
          openConversation(id, { id, type: 'public', title }, null);
        }
      } catch (_) { }
    });
  }

  // If the public conversation id is already available (from Blade prefetch), ensure badge and bell exist
  const preId = window.PUBLIC_CHAT_ID || publicLi?.dataset?.conversationId;
  if (preId) {
    ensurePublicBadgeBell(preId);
  }

  document.getElementById('chat-message-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const input = document.getElementById('chat-message-input');
    if (!input.value.trim() || !window.currentConversationId) return;
    fetch(window.chatRoutes.sendMessage.replace(':id', window.currentConversationId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      credentials: 'same-origin',
      body: JSON.stringify({ body: input.value.trim(), type: 'text' })
    }).then(async res => {
      if (!res.ok) {
        let data = {};
        try { data = await res.json(); } catch (e) { }
        console.error('Error response:', data);
        showAlert('failure', data.message || 'غير مسموح بإرسال الرسائل.');
        return;
      }
      const data = await res.json();
      if (data.message) {
        appendMessageToChat(data.message);
        input.value = '';
      }
    });
  });

  document.getElementById('chat-history-list')?.addEventListener('click', function (e) {
    let target = e.target;
    if (target.closest('.avatar-sm') || target.classList.contains('chat-username')) {
      let userId = target.closest('[data-user-id]')?.getAttribute('data-user-id');
      if (userId) {
        showContactSidebar(userId);
      }
    }
  });

  document.getElementById('active-chat-avatar')?.addEventListener('click', function () {
    if (window.currentChatUserId) showContactSidebar(window.currentChatUserId);
  });
  document.getElementById('active-chat-name')?.addEventListener('click', function () {
    if (window.currentChatUserId) showContactSidebar(window.currentChatUserId);
  });



  function clearChat() {
    if (!window.currentConversationId) return;
    const doClear = () => {
      fetch(window.chatRoutes.clear.replace(':id', window.currentConversationId), {
        method: 'DELETE',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        credentials: 'same-origin'
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.getElementById('chat-history-list').innerHTML =
              `<li class="text-center text-muted p-3">لا توجد رسائل</li>`;
            showAlert(ALERT_TYPES.success, 'تم مسح المحادثة بنجاح', {
              title: 'تم المسح'
            });
          } else {
            showAlert(ALERT_TYPES.danger, data.message || 'حدث خطأ أثناء المسح', {
              title: 'خطأ'
            });
          }
        })
        .catch(() => {
          showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء المسح', {
            title: 'خطأ'
          });
        });
    };

    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        title: 'تأكيد المسح',
        text: 'هل أنت متأكد من مسح جميع رسائل هذه المحادثة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء',
        customClass: {
          confirmButton: 'btn btn-danger',
          cancelButton: 'btn btn-outline-secondary ms-1'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          doClear();
        }
      });
    } else {
      if (confirm('هل أنت متأكد من مسح جميع رسائل هذه المحادثة؟')) doClear();
    }
  }

  /**
 * UI HELPERS
 */
function scrollToBottom() {
  const container = document.querySelector('.chat-history-body');
  if (container) container.scrollTop = container.scrollHeight;
}



/**
 * CHAT BOX STATE MANAGEMENT
 */
function lockChatBox(isLocked) {
  const chatInput = document.getElementById('chat-message-input');
  const sendBtn = document.querySelector('#chat-message-form button[type="submit"]');
  if (chatInput) chatInput.disabled = !!isLocked;
  if (sendBtn) sendBtn.disabled = !!isLocked;
}

function updateBlockButtons({ i_blocked, blocked_me }) {
  const blockBtn = document.getElementById('block-contact-btn');
  const unblockBtn = document.getElementById('unblock-contact-btn');
  const chatInput = document.getElementById('chat-message-input');
  const sendBtn = document.querySelector('#chat-message-form button[type="submit"]');

  if (i_blocked) {
    // أنت قمت بالحظر
    blockBtn?.classList.add('d-none');
    unblockBtn?.classList.remove('d-none');
    if (chatInput) chatInput.disabled = true;
    if (sendBtn) sendBtn.disabled = true;
  } else if (blocked_me) {
    // الطرف الآخر قام بحظرك
    blockBtn?.classList.add('d-none');
    unblockBtn?.classList.add('d-none');
    if (chatInput) chatInput.disabled = true;
    if (sendBtn) sendBtn.disabled = true;
  } else {
    // لا يوجد حظر
    blockBtn?.classList.remove('d-none');
    unblockBtn?.classList.add('d-none');
    if (chatInput) chatInput.disabled = false;
    if (sendBtn) sendBtn.disabled = false;
  }
}

// جلب حالة الحظر للطرفين من السيرفر
function checkBlockStatus(userId) {
  if (!userId) return;
  fetch(window.chatRoutes.blockStatus.replace(':id', userId), {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json'
    },
    credentials: 'same-origin'
  })
    .then(res => res.json())
    .then(updateBlockButtons)
    .catch(() => {});
}


/**
 * BLOCK/UNBLOCK/REPORT/CLEAR
 */
function blockUser(userId) {
  if (!userId) return;
    fetch(window.chatRoutes.block.replace(':id', userId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(() => {
            checkBlockStatus(userId);
            showAlert(ALERT_TYPES.success, 'تم حظر المستخدم بنجاح', {
                title: 'تم الحظر'
            });
        })
        .catch(error => {
            console.error('Error blocking user:', error);
            showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء حظر المستخدم', {
                title: 'خطأ'
            });
        });
}

function unblockUser(userId) {
  if (!userId) return;
    fetch(window.chatRoutes.unblock.replace(':id', userId), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(() => {
            checkBlockStatus(userId);
            showAlert(ALERT_TYPES.success, 'تم فك حظر المستخدم بنجاح', {
                title: 'تم فك الحظر'
            });
        })
        .catch(error => {
            console.error('Error unblocking user:', error);
            showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء فك الحظر', {
                title: 'خطأ'
            });
        });
}


function reportUser() {
  if (!window.currentChatUserId) return;
  const openReportModal = () => {
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        title: 'الإبلاغ عن المستخدم',
        input: 'textarea',
        inputPlaceholder: 'سبب الإبلاغ...',
        showCancelButton: true,
        confirmButtonText: 'إرسال',
        cancelButtonText: 'إلغاء',
        inputAttributes: { dir: 'auto' },
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-outline-secondary ms-1'
        },
        preConfirm: value => {
          if (!value || !value.trim()) {
            window.Swal.showValidationMessage('يرجى كتابة سبب الإبلاغ');
          }
          return value;
        }
      }).then(result => {
        if (result.isConfirmed && result.value) {
          sendReport(result.value.trim());
        }
      });
    } else {
      const reason = prompt('يرجى كتابة سبب الإبلاغ:');
      if (reason && reason.trim()) sendReport(reason.trim());
    }
  };

  const sendReport = reason => {
    fetch(`/dashboard/chat/report/${window.currentChatUserId}`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ reason })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showAlert(ALERT_TYPES.success, 'تم الإبلاغ بنجاح', {
            title: 'تم الإبلاغ'
          });
        } else {
          showAlert(ALERT_TYPES.danger, data.message || 'حدث خطأ أثناء الإبلاغ', {
            title: 'خطأ'
          });
        }
      })
      .catch(() => {
        showAlert(ALERT_TYPES.danger, 'حدث خطأ أثناء الإبلاغ', {
          title: 'خطأ'
        });
      });
  };

  openReportModal();
}


  document.getElementById('clear-chat')?.addEventListener('click', clearChat);
// Block/Unblock/Clear/Report listeners
document.getElementById('block-contact-btn')?.addEventListener('click', function () {
  blockUser(window.currentChatUserId);
});
document.getElementById('unblock-contact-btn')?.addEventListener('click', function () {
  unblockUser(window.currentChatUserId);
});
document.getElementById('report-contact')?.addEventListener('click', reportUser);

/**
 * GLOBAL EXPORTS (اختياري)
 */
window.showContactSidebar = showContactSidebar;
window.openConversation = openConversation;
window.renderMessages = renderMessages;
window.createPrivateChat = createPrivateChat;
window.loadContacts = loadContacts;
window.loadConversations = loadConversations;
window.appendMessageToChat = appendMessageToChat;
});
