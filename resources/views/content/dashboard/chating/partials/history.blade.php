<div class="col app-chat-history d-none" id="app-chat-history">
  <div class="chat-history-wrapper">
    <div class="chat-history-header border-bottom">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex overflow-hidden align-items-center">
          <i class="icon-base ti tabler-menu-2 icon-lg cursor-pointer d-lg-none d-block me-4" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-contacts"></i>
          <div class="flex-shrink-0 avatar avatar-sm" id="active-chat-avatar-wrapper" data-user-id="{{ $currentUser->id ?? '' }}">
            <img src="{{ $otherUser->avatar }}" alt="Avatar" class="rounded-circle" />

                 class="rounded-circle"
                 data-bs-toggle="sidebar"
                 data-overlay
                 data-target="#app-chat-sidebar-right"
                 id="active-chat-avatar"
                 style="width: 30px; height: 30px; object-fit: cover;">
            <span class="avatar-status {{ $currentUser->is_online ?? false ? 'avatar-status-success' : 'avatar-status-offline' }}"></span>
          </div>
        </div>
        <div class="chat-contact-info flex-grow-1 ms-4">
          <h6 class="m-0 fw-normal" id="active-chat-name">Select Chat</h6>
          <small class="user-status text-body" id="active-chat-status">Online</small>
        </div>
      </div>
    </div>
    <div class="chat-history-body">
      <ul class="list-unstyled chat-history" id="chat-history-list">
      </ul>
    </div>
    <!-- Chat message form -->
    <div class="chat-history-footer shadow-xs">
      <form class="form-send-message d-flex justify-content-between align-items-center" id="chat-message-form">
        <input class="form-control message-input border-0 me-4 shadow-none"
          placeholder="Type your message here..." id="chat-message-input" />
        <div class="message-actions d-flex align-items-center">
          <button class="btn btn-primary d-flex send-msg-btn">
            <span class="align-middle d-md-inline-block d-none">Send</span>
            <i class="icon-base ti tabler-send icon-16px ms-md-2 ms-0"></i>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
