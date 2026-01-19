<div class="col app-chat-contacts app-sidebar flex-grow-0 overflow-hidden border-end d-flex flex-column" id="app-chat-contacts" style="height: 100vh;">
  <div class="sidebar-header h-px-75 px-5 border-bottom d-flex align-items-center">
    <div class="d-flex align-items-center me-6 me-lg-0">
        <div class="flex-shrink-0 avatar avatar-online me-4" data-bs-toggle="sidebar" data-overlay="app-overlay-ex" data-target="#app-chat-sidebar-left">
                    @if(auth()->check() && auth()->user()->profile_photo_path)
                      <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}?{{ time() }}"
                          alt="{{ auth()->user()->name }}"
                          class="rounded-circle h-100 w-100 object-fit-cover"
                          onerror="this.onerror=null; this.src='{{ auth()->user()->profile_photo_url }}';">
                    @else
                      <div class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center h-100">
                        <i class="tabler-user icon-base ti icon-lg text-muted"></i>
                      </div>
                    @endif
        </div>
      <div class="flex-grow-1 input-group input-group-merge">
        <span class="input-group-text" id="basic-addon-search31"><i class="icon-base ti tabler-search icon-xs"></i></span>
        <input type="text" class="form-control chat-search-input" placeholder="Search..." aria-label="Search..." aria-describedby="basic-addon-search31" />
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
      <li class="chat-contact-list-item chat-list-item-0 d-none">
        <h6 class="text-body-secondary mb-0">No Chats Found</h6>
      </li>
    </ul>
    <!-- Contacts -->
    <ul class="list-unstyled chat-contact-list mb-0 py-2" id="contact-list">
      <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
        <h5 class="text-primary mb-0">Contacts</h5>
      </li>
      <li class="chat-contact-list-item contact-list-item-0 d-none">
        <h6 class="text-body-secondary mb-0">No Contacts Found</h6>
      </li>
    </ul>
    <div id="contact-load-more" class="p-3 text-center d-none">
      <button type="button" id="contact-load-more-btn" class="btn btn-outline-primary btn-sm">
        Load more
      </button>
    </div>
  </div>
</div>
