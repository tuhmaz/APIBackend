<div class="col app-chat-sidebar-left app-sidebar overflow-hidden" id="app-chat-sidebar-left">
  <div class="chat-sidebar-left-user sidebar-header d-flex flex-column justify-content-center align-items-center flex-wrap px-6 pt-12">
    <div class="avatar avatar-xl avatar-online chat-sidebar-avatar">
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
    <h5 class="mt-4 mb-0">{{ \Illuminate\Support\Facades\Auth::user()->name }}</h5>
    <span>{{ \Illuminate\Support\Facades\Auth::user()->role ?? 'Member' }}</span>
    <i class="icon-base ti tabler-x icon-lg cursor-pointer close-sidebar" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-sidebar-left"></i>
  </div>
  <div class="sidebar-body px-6 pb-6">
    <div class="my-6">
      <div class="maxLength-wrapper">
        <label for="chat-sidebar-left-user-about" class="text-uppercase text-body-secondary mb-1">About</label>
        <textarea id="chat-sidebar-left-user-about" class="form-control chat-sidebar-left-user-about maxLength-example" rows="3" maxlength="120">{{ \Illuminate\Support\Facades\Auth::user()->about ?? '' }}</textarea>
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
      <button class="btn btn-primary w-100" data-bs-toggle="sidebar" data-overlay data-target="#app-chat-sidebar-left">Logout<i class="icon-base ti tabler-logout icon-16px ms-2"></i></button>
    </div>
  </div>
</div>
