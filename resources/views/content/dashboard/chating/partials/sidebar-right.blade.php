<div class="col app-chat-sidebar-right app-sidebar overflow-hidden" id="app-chat-sidebar-right">
  <div class="sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-12"
    id="active-chat-sidebar-avatar">
    <div class="avatar avatar-xl position-relative" id="active-chat-avatar-wrapper">
      <img src="" alt="Avatar" class="rounded-circle" />
    </div>
    <h5 class="mt-4 mb-0" id="sidebar-contact-name">Contact Name</h5>
    <span id="sidebar-contact-role">Role/Title</span>
    <i class="icon-base ti tabler-x icon-lg cursor-pointer close-sidebar d-block" data-bs-toggle="sidebar" data-overlay
      data-target="#app-chat-sidebar-right"></i>
  </div>
  <div class="sidebar-body p-6 pt-0">
    <div class="my-6">
      <p class="text-uppercase mb-1 text-body-secondary">About</p>
      <p class="mb-0" id="sidebar-contact-about">No details...</p>
    </div>
    <div class="my-6">
      <p class="text-uppercase mb-1 text-body-secondary">Personal Information</p>
      <ul class="list-unstyled d-grid gap-4 mb-0 ms-2 py-2 text-heading">
        <li class="d-flex align-items-center">
          <i class="icon-base ti tabler-mail icon-md"></i>
          <span class="align-middle ms-2" id="sidebar-contact-email">user@email.com</span>
        </li>
        <li class="d-flex align-items-center">
          <i class="icon-base ti tabler-clock icon-md"></i>
          <span class="align-middle ms-2" id="sidebar-contact-status">Offline</span>
        </li>
      </ul>
    </div>
    <div class="my-6">
      <p class="text-uppercase text-body-secondary mb-1">Options</p>
      <ul class="list-unstyled d-grid gap-4 ms-2 py-2 text-heading">
        <li class="cursor-pointer d-flex align-items-center" id="block-contact-btn" onclick="blockUser(currentChatUserId)">
          <i class="icon-base ti tabler-ban icon-md"></i>
          <span class="align-middle ms-2">حظر المستخدم</span>
        </li>
        <li class="cursor-pointer d-flex align-items-center d-none" id="unblock-contact-btn" onclick="unblockUser(currentChatUserId)">
          <i class="icon-base ti tabler-arrow-back-up icon-md"></i>
          <span class="align-middle ms-2">إلغاء الحظر</span>
        </li>
        <li class="cursor-pointer d-flex align-items-center" id="clear-chat" onclick="clearChat()">
          <i class="icon-base ti tabler-trash icon-md"></i>
          <span class="align-middle ms-2">مسح المحادثة</span>
        </li>
        <li class="cursor-pointer d-flex align-items-center" id="report-contact" onclick="reportUser()">
          <i class="icon-base ti tabler-flag icon-md"></i>
          <span class="align-middle ms-2">الإبلاغ عن المستخدم</span>
        </li>
</ul>
    </div>
    <div class="d-flex mt-6">
      <button class="btn btn-danger w-100" data-bs-toggle="sidebar" data-overlay
        data-target="#app-chat-sidebar-right">Delete chating<i
          class="icon-base ti tabler-trash icon-16px ms-2"></i></button>
    </div>
  </div>
</div>
