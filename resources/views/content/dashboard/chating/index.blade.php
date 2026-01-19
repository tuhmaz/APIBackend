@extends('layouts/layoutMaster')

@section('title', 'Chat - Apps')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/maxLength/maxLength.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/app-chat.scss',

  ])
@endsection

@section('page-script')
  {{-- تعريف المستخدم الحالي و مسارات الدردشة --}}
  <script>
    window.currentUser = @json(auth()->user());
    // Inject explicit role info for reliable frontend checks
    @php($__roles = auth()->user() && method_exists(auth()->user(), 'getRoleNames') ? auth()->user()->getRoleNames() : collect())
    window.currentUserRoles = @json($__roles);
    @php($__isSuper = auth()->user() && method_exists(auth()->user(), 'hasRole') ? auth()->user()->hasRole('super_admin') : false)
    window.isSuperAdmin = @json($__isSuper);
    window.chatRoutes = {
        conversations: "{{ route('dashboard.chat.conversations') }}",
        users: "{{ route('dashboard.chat.users') }}",
        // Use named parameters to keep ":id" placeholder
        sendMessage: "{{ route('dashboard.chat.send', ['conversation' => ':id']) }}",
        messages: "{{ route('dashboard.chat.messages', ['id' => ':id']) }}",
        createPrivate: "{{ route('dashboard.chat.createPrivateConversation') }}",
        public: "{{ route('dashboard.chat.public') }}",
        // Use web route for unread counts to leverage session auth and avoid 401 on API
        unread: "{{ route('dashboard.chat.unread-counts') }}",
        // Extra web routes to avoid API 401 on session-authenticated page
        user: "{{ route('dashboard.chat.user', ['id' => ':id']) }}",
        block: "{{ route('dashboard.chat.block', ['id' => ':id']) }}",
        unblock: "{{ route('dashboard.chat.unblock', ['id' => ':id']) }}",
        blockStatus: "{{ route('dashboard.chat.block-status', ['id' => ':id']) }}",
        clear: "{{ route('dashboard.chat.clear', ['conversation' => ':id']) }}",
    };
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const publicLi = document.getElementById('public-chat-li');
      if (publicLi && window.chatRoutes?.public) {
        fetch(window.chatRoutes.public, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
          .then(r => r.json())
          .then(d => {
            if (d?.id) {
              publicLi.dataset.conversationId = d.id;
              window.PUBLIC_CHAT_ID = d.id;
            }
          })
          .catch(() => {});
      }
    });
  </script>
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/js/chat/chat.js'
  ])
@endsection

@section('content')
<div class="app-chat card overflow-hidden">
  <div class="row g-0">
    <!-- Sidebar Left (معلومات المستخدم واختصارات) -->
    <div class="col app-chat-sidebar-left app-sidebar overflow-hidden" id="app-chat-sidebar-left">
      <div class="chat-sidebar-left-user sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-12">
        <div class="avatar avatar-xl avatar-online chat-sidebar-avatar">
          <img src="{{ auth()->user()?->avatar ?? asset('assets/img/avatars/1.png') }}" alt="Avatar" class="rounded-circle" />
        </div>
        <h5 class="mt-4 mb-0">{{ auth()->user()?->name ?? 'User Name' }}</h5>
        <span>{{ auth()->user()?->role?->name ?? 'Member' }}</span>
        <div class="mt-3 d-flex gap-2">
          <button id="chat-mute-toggle" type="button" class="btn btn-sm btn-outline-secondary" aria-pressed="false">
            <i class="bx bx-bell"></i>
            <span class="label">كتم الإشعارات الصوتية</span>
          </button>
        </div>
        <i class="icon-base ti tabler-x icon-lg cursor-pointer close-sidebar" data-bs-toggle="sidebar" data-overlay
          data-target="#app-chat-sidebar-left"></i>
      </div>
      <div class="sidebar-body px-6 pb-6">
        <div class="my-6">
          <div class="maxLength-wrapper">
            <label for="chat-sidebar-left-user-about" class="text-uppercase text-body-secondary mb-1">About</label>
            <textarea id="chat-sidebar-left-user-about"
              class="form-control chat-sidebar-left-user-about maxLength-example" rows="3"
              maxlength="120">{{ auth()->user()?->about ?? '' }}</textarea>
            <small id="textarea-maxlength-info"></small>
          </div>
        </div>
        <div class="my-6">
          <p class="text-uppercase text-body-secondary mb-1">Status</p>
          <div class="d-grid gap-2 pt-2 text-heading ms-2">
            <div class="form-check form-check-success">
              <input name="chat-user-status" class="form-check-input" type="radio" value="active" id="user-active" checked />
              <label class="form-check-label" for="user-active">Online</label>
            </div>
            <div class="form-check form-check-warning">
              <input name="chat-user-status" class="form-check-input" type="radio" value="away" id="user-away" />
              <label class="form-check-label" for="user-away">Away</label>
            </div>
            <div class="form-check form-check-danger">
              <input name="chat-user-status" class="form-check-input" type="radio" value="busy" id="user-busy" />
              <label class="form-check-label" for="user-busy">Do not Disturb</label>
            </div>
            <div class="form-check form-check-secondary">
              <input name="chat-user-status" class="form-check-input" type="radio" value="offline" id="user-offline" />
              <label class="form-check-label" for="user-offline">Offline</label>
            </div>
          </div>
        </div>

        <div class="d-flex mt-6">
          <button class="btn btn-primary w-100" id="logout-btn">Logout<i class="icon-base ti tabler-logout icon-16px ms-2"></i></button>
        </div>
      </div>
    </div>
    <!-- /Sidebar Left-->

    <!-- Chat & Contacts -->
    <div class="col app-chat-contacts app-sidebar flex-grow-0 overflow-hidden border-end d-flex flex-column" id="app-chat-contacts" style="height: 100vh;">
      <div class="sidebar-header h-px-75 px-5 border-bottom d-flex align-items-center">
        <div class="d-flex align-items-center me-6 me-lg-0">
          <div class="flex-shrink-0 avatar avatar-online me-4" data-bs-toggle="sidebar" data-overlay="app-overlay-ex"
            data-target="#app-chat-sidebar-left">
            <img class="user-avatar rounded-circle cursor-pointer" src="{{ auth()->user()?->avatar ?? asset('assets/img/avatars/1.png') }}"
              alt="Avatar" />
          </div>
          <div class="flex-grow-1 input-group input-group-merge">
            <span class="input-group-text" id="basic-addon-search31"><i
                class="icon-base ti tabler-search icon-xs"></i></span>
            <input type="text" class="form-control chat-search-input" placeholder="Search..." aria-label="Search..."
              aria-describedby="basic-addon-search31" />
          </div>
        </div>
        <!-- Filter: Online only -->
        <div class="w-100 mt-3">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="contacts-online-only">
              <label class="form-check-label" for="contacts-online-only">Online فقط</label>
            </div>
          </div>
        </div>
        <i class="icon-base ti tabler-x icon-lg cursor-pointer position-absolute top-50 end-0 translate-middle d-lg-none d-block"
          data-overlay data-bs-toggle="sidebar" data-target="#app-chat-contacts"></i>
      </div>
      <div class="sidebar-body flex-grow-1" style="overflow: auto; min-height: 0;">
        <!-- Chats -->
        <ul class="list-unstyled chat-contact-list py-2 mb-0" id="chat-list">
          <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
            <h5 class="text-primary mb-0">Chats</h5>
          </li>
          <!-- Public chat pinned item -->
          <li class="chat-contact-list-item d-flex align-items-center cursor-pointer" id="public-chat-li" data-conversation-id="">
            <div class="d-flex align-items-center w-100">
              <div class="flex-shrink-0 avatar me-4">
                <span class="avatar-initial rounded-circle bg-label-primary"><i class="icon-base ti tabler-users"></i></span>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">الدردشة العامة</h6>
                <small class="text-body-secondary">غرفة عامة لكل الأعضاء</small>
              </div>
            </div>
          </li>
          <li class="chat-contact-list-item chat-list-item-0 d-none">
            <h6 class="text-body-secondary mb-0">No Chats Found</h6>
          </li>
          {{-- سيتم تعبئة الشات ديناميكياً --}}
        </ul>
        <!-- Contacts -->
        <ul class="list-unstyled chat-contact-list mb-0 py-2" id="contact-list">
          <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
            <h5 class="text-primary mb-0">Contacts</h5>
          </li>
          <li class="chat-contact-list-item contact-list-item-0 d-none">
            <h6 class="text-body-secondary mb-0">No Contacts Found</h6>
          </li>
          {{-- سيتم تعبئة الكونتاكت ديناميكياً --}}
        </ul>
        <div id="contact-load-more" class="p-3 text-center d-none">
          <button type="button" id="contact-load-more-btn" class="btn btn-outline-primary btn-sm">
            Load more
          </button>
        </div>
      </div>
    </div>
    <!-- /Chat contacts -->

    <!-- Chat conversation when not selected -->
    <div class="col app-chat-conversation d-flex align-items-center justify-content-center flex-column"
      id="app-chat-conversation">
      <div class="bg-label-primary p-8 rounded-circle">
        <i class="icon-base ti tabler-message-2 icon-50px"></i>
      </div>
      <p class="my-4">Select a contact to start a conversation.</p>
      <button class="btn btn-primary app-chat-conversation-btn" id="app-chat-conversation-btn">Select Contact</button>
    </div>
    <!-- /Chat conversation -->

    <!-- Chat History (dynamic) -->
    <div class="col app-chat-history d-none" id="app-chat-history">
      <div class="chat-history-wrapper d-flex flex-column h-100">
        <div class="chat-history-header border-bottom">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex overflow-hidden align-items-center">
              <i class="icon-base ti tabler-menu-2 icon-lg cursor-pointer d-lg-none d-block me-4"
                data-bs-toggle="sidebar" data-overlay data-target="#app-chat-contacts"></i>
              <div class="flex-shrink-0 avatar" id="active-chat-avatar-wrapper">
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="rounded-circle"
                  id="active-chat-avatar" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-sidebar-right" />
              </div>
             <div class="chat-contact-info flex-grow-1 ms-4">
    <h6 class="m-0 fw-normal" id="active-chat-name">اسم العضو</h6>
    <small class="user-status text-body" id="active-chat-status">غير متصل</small>
</div>

            </div>
            <div class="d-flex align-items-center">
              <div class="dropdown">
                <button class="btn btn-icon btn-text-secondary text-secondary rounded-pill dropdown-toggle hide-arrow"
                  data-bs-toggle="dropdown" aria-expanded="true" id="chat-header-actions"><i
                    class="icon-base ti tabler-dots-vertical icon-22px"></i></button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="chat-header-actions">
                  <a class="dropdown-item" href="#" id="block-contact-btn">Block Contact</a>
                  <a class="dropdown-item" href="#" id="unblock-contact-btn" style="display:none;">Unblock Contact</a>
                  <a class="dropdown-item" href="#" id="clear-chat">Clear Chat</a>
                  <a class="dropdown-item" href="#" id="report-contact">Report</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="chat-history-body flex-grow-1 overflow-auto" style="background:#fafbfc;">
          <ul class="list-unstyled chat-history px-3 pt-3" id="chat-history-list" style="min-height:300px;">
            {{-- الرسائل من chat.js --}}
          </ul>
        </div>
        <!-- Chat message form -->
        <div class="chat-history-footer shadow-xs">
          <form class="form-send-message d-flex justify-content-between align-items-center" id="chat-message-form" autocomplete="off">
            <input class="form-control message-input border-0 me-4 shadow-none" id="chat-message-input"
              placeholder="Type your message here..." maxlength="2000" required autocomplete="off" />
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
    <!-- /Chat History -->

    <!-- Sidebar Right (بيانات المستخدم الآخر) -->
    <div class="col app-chat-sidebar-right app-sidebar overflow-hidden" id="app-chat-sidebar-right" style="display:none;">
      <div class="sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-12">
        <div class="avatar avatar-xl avatar-online chat-sidebar-avatar" id="active-chat-sidebar-avatar">
          <img src="{{ asset('assets/img/avatars/4.png') }}" alt="{{ $user->name }}" class="rounded-circle" id="active-chat-avatar" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-sidebar-right" />
        </div>
        <h5 class="mt-4 mb-0" id="sidebar-contact-name">اسم العضو</h5>
        <span id="sidebar-contact-role">الدور</span>
        <i class="icon-base ti tabler-x icon-lg cursor-pointer close-sidebar d-block" data-bs-toggle="sidebar"
          data-overlay data-target="#app-chat-sidebar-right"></i>
      </div>
      <div class="sidebar-body p-6 pt-0">
        <div class="my-6">
          <p class="text-uppercase mb-1 text-body-secondary">About</p>
          <p class="mb-0" id="sidebar-contact-about">لا يوجد تفاصيل...</p>
        </div>
        <div class="my-6">
          <p class="text-uppercase mb-1 text-body-secondary">Personal Information</p>
          <ul class="list-unstyled d-grid gap-4 mb-0 ms-2 py-2 text-heading">
            <li class="d-flex align-items-center">
              <i class="icon-base ti tabler-mail icon-md"></i>
              <span class="align-middle ms-2" id="sidebar-contact-email">email@email.com</span>
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
            <li class="cursor-pointer d-flex align-items-center">
              <i class="icon-base ti tabler-bookmark icon-md"></i>
              <span class="align-middle ms-2">Add Tag</span>
            </li>
            <li class="cursor-pointer d-flex align-items-center">
              <i class="icon-base ti tabler-star icon-md"></i>
              <span class="align-middle ms-2">Important Contact</span>
            </li>
            <li class="cursor-pointer d-flex align-items-center">
              <i class="icon-base ti tabler-photo icon-md"></i>
              <span class="align-middle ms-2">Shared Media</span>
            </li>
            <li class="cursor-pointer d-flex align-items-center">
              <i class="icon-base ti tabler-ban icon-md"></i>
              <span class="align-middle ms-2">Block Contact</span>
            </li>
          </ul>
        </div>
        <div class="d-flex mt-6">
          <button class="btn btn-danger w-100" id="delete-contact-btn">Delete Contact<i
              class="icon-base ti tabler-trash icon-16px ms-2"></i></button>
        </div>
      </div>
    </div>
    <!-- /Sidebar Right -->

    <div class="app-overlay"></div>
  </div>
</div>
@endsection
