@extends('layouts/contentNavbarLayout')

@section('title', __('Messages'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss',
  'resources/assets/vendor/scss/pages/app-email.scss',
  'resources/assets/vendor/libs/quill/typography.scss',

  'resources/assets/vendor/libs/quill/editor.scss'
])
<style>
  .email-list {
    position: relative;
    height: calc(100vh - 15rem);
  }
  .email-list .email-list-item {
    padding: 1rem;
    transition: all 0.2s ease-in-out;
    margin: 0.25rem 0;
    border-radius: 0.5rem;
  }
  .email-list .email-list-item:hover {
    background-color: rgba(67, 89, 113, 0.04);
    cursor: pointer;
  }
  .email-list .email-list-item.email-unread {
    background-color: rgba(67, 89, 113, 0.02);
  }
  .email-list .email-list-item.email-unread .email-list-item-username,
  .email-list .email-list-item.email-unread .email-list-item-subject {
    font-weight: 500;
  }
  .email-navigation-list .navigation-item {
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease-in-out;
    margin: 0.25rem 0;
  }
  .email-navigation-list .navigation-item:hover {
    background-color: rgba(67, 89, 113, 0.04);
  }
  .email-navigation-list .navigation-item.active {
    background-color: #696cff;
    color: #fff;
  }
  .email-navigation-list .navigation-item.active .badge {
    background-color: #fff !important;
    color: #696cff !important;
  }

  /* Modal Styles */
  .message-modal .modal-content {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  }
  .message-modal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1.5rem;
  }
  .message-modal .modal-title {
    color: #566a7f;
    font-weight: 600;
    margin: 0;
  }
  .message-modal .modal-body {
    padding: 1.5rem;
  }
  .message-modal .message-info {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }
  .message-modal .sender-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    margin-right: 1rem;
  }
  .message-modal .sender-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #566a7f;
    margin-bottom: 0.25rem;
  }
  .message-modal .message-timestamp {
    font-size: 0.875rem;
    color: #a1acb8;
  }
  .message-modal .message-content {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-top: 1rem;
    border: 1px solid #e9ecef;
  }
  .message-modal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
  }
  .message-modal .btn-reply {
    background-color: #696cff;
    border-color: #696cff;
    padding: 0.5rem 1.5rem;
  }
  .message-modal .btn-reply:hover {
    background-color: #5f65f4;
    border-color: #5f65f4;
  }
  .message-modal .message-reply {
    margin-top: 1.5rem;
    border-top: 1px solid #e9ecef;
    padding-top: 1.5rem;
  }
  .message-modal .message-reply-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
  }
  .message-modal .message-reply-toggle {
    color: #696cff;
    cursor: pointer;
    font-weight: 500;
  }
  .message-modal .message-reply-toggle:hover {
    text-decoration: underline;
  }
  .message-modal .message-reply-form {
    display: none;
  }
  .message-modal .message-reply-form.show {
    display: block;
  }
  .message-modal .message-reply-editor {
    height: 150px;
    margin-bottom: 1rem;
  }
  .message-modal .ql-toolbar.ql-snow,
  .message-modal .ql-container.ql-snow {
    border-color: #e9ecef;
  }
  .message-modal .ql-editor {
    min-height: 100px;
  }
  .message-modal .quick-reply-btn {
    background-color: #696cff;
    border-color: #696cff;
  }
  .message-modal .quick-reply-btn:hover {
    background-color: #5f65f4;
    border-color: #5f65f4;
  }
  .message-modal .quick-reply-btn:disabled {
    background-color: #b3b5ff;
    border-color: #b3b5ff;
  }
</style>
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'resources/assets/vendor/libs/typeahead-js/typeahead.js',

  'resources/assets/vendor/libs/quill/quill.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app-email.js'])
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.email-list .email-list-wrapper');
    const nav = document.querySelector('.email-navigation-list');
    if (!container || !nav) return;

    function setActive(link) {
      nav.querySelectorAll('.navigation-item').forEach(a => a.classList.remove('active'));
      link.classList.add('active');
    }

    async function loadPartial(url, link) {
      try {
        const partialUrl = url.includes('?') ? url + '&partial=1' : url + '?partial=1';
        const res = await fetch(partialUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error('Network');
        const html = await res.text();
        container.innerHTML = html;
        if (link) {
          setActive(link);
          window.history.pushState({ url }, '', url);
        }
      } catch (e) {
        console.error(e);
      }
    }

    nav.addEventListener('click', function (e) {
      const a = e.target.closest('a.navigation-item');
      if (!a) return;
      const href = a.getAttribute('href');
      if (!href) return;
      // Only hijack links that belong to the messages module
      const isMessagesRoute = href.startsWith('/dashboard/messages') || href.includes('/dashboard/messages');
      if (!isMessagesRoute) {
        // Allow normal navigation for non-messages routes (e.g., Chat page)
        return;
      }
      e.preventDefault();
      loadPartial(href, a);
    });

    window.addEventListener('popstate', function (e) {
      const url = (e.state && e.state.url) || window.location.href;
      loadPartial(url, null);
    });

    // Intercept pagination clicks within the list container
    container.addEventListener('click', function (e) {
      const a = e.target.closest('.pagination a');
      if (!a) return;
      const href = a.getAttribute('href');
      if (!href) return;
      e.preventDefault();
      loadPartial(href, null);
    });

    // Compose modal trigger: prevent navigation and open modal
    document.querySelectorAll('.compose-trigger').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const modalEl = document.getElementById('composeModal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
          const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        }
      });
    });

    // Quick Reply inside message modal
    function initQuickReply(modalEl) {
      const toggle = modalEl.querySelector('.message-reply-toggle');
      const formWrap = modalEl.querySelector('.message-reply-form');
      const form = modalEl.querySelector('form.quick-reply-form');
      const editorEl = modalEl.querySelector('.message-reply-editor');
      if (!form || !editorEl) return;

      // Toggle show/hide
      if (toggle && formWrap) {
        toggle.addEventListener('click', () => {
          formWrap.classList.add('show');
          // Focus editor after showing
          setTimeout(() => {
            if (editorEl.__quill) {
              editorEl.__quill.focus();
            } else {
              editorEl.focus();
            }
          }, 50);
        }, { once: true });
      }

      // Initialize Quill once
      if (window.Quill) {
        if (!editorEl.__quill) {
          const quill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: '{{ __('Type your reply...') }}',
            modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link']] }
          });
          editorEl.__quill = quill;
        }
      } else {
        // Fallback: make the editor area contenteditable
        editorEl.setAttribute('contenteditable', 'true');
        editorEl.classList.add('form-control');
        editorEl.style.minHeight = '150px';
      }

      // Submit handler
      form.addEventListener('submit', function (e) {
        const quill = editorEl.__quill;
        const html = quill ? quill.root.innerHTML.trim() : editorEl.innerHTML.trim();
        const text = quill ? quill.getText().trim() : editorEl.textContent.trim();
        const hidden = form.querySelector('.quick-reply-message-input');
        const error = form.querySelector('.quick-reply-error');
        if (!text) {
          e.preventDefault();
          if (error) error.style.display = 'block';
          return;
        }
        if (error) error.style.display = 'none';
        if (hidden) hidden.value = html;
      }, { once: false });

      // Auto-show the quick reply form when modal opens
      if (formWrap && !formWrap.classList.contains('show')) {
        formWrap.classList.add('show');
        setTimeout(() => {
          if (editorEl.__quill) editorEl.__quill.focus();
          else editorEl.focus();
        }, 50);
      }
    }

    // Attach on modal show
    document.querySelectorAll('.message-modal').forEach(modalEl => {
      modalEl.addEventListener('shown.bs.modal', function () {
        initQuickReply(modalEl);
      });
    });

    // Delete confirmation handler
    const deleteRouteTemplate = "{{ route('dashboard.messages.delete', ['id' => '__ID__']) }}";
    function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    document.querySelectorAll('.confirm-delete').forEach(btn => {
      btn.addEventListener('click', async function () {
        const id = this.getAttribute('data-message-id');
        if (!id) return;
        const url = deleteRouteTemplate.replace('__ID__', id);
        try {
          const res = await fetch(url, {
            method: 'DELETE',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': getCsrfToken(),
              'Accept': 'application/json'
            }
          });
          // Laravel may redirect on non-AJAX; handle both
          if (!res.ok && res.status !== 204) {
            // Try to follow redirect by reloading
            window.location.reload();
            return;
          }
          // Hide delete modal
          const deleteModal = this.closest('.modal');
          if (deleteModal && window.bootstrap && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(deleteModal) || bootstrap.Modal.getOrCreateInstance(deleteModal);
            inst.hide();
          }
          // Hide parent message modal if open
          const parentMsgModal = document.getElementById('messageModal' + id);
          if (parentMsgModal && window.bootstrap && bootstrap.Modal) {
            const inst2 = bootstrap.Modal.getInstance(parentMsgModal);
            if (inst2) inst2.hide();
          }
          // Remove item from list
          const item = document.querySelector('.email-list-item[data-message-id="' + id + '"]');
          if (item) {
            const hr = item.nextElementSibling;
            item.remove();
            if (hr && hr.tagName === 'HR') hr.remove();
          }
        } catch (e) {
          console.error(e);
          // fallback
          window.location.reload();
        }
      });
    });
  });
</script>
@endsection

@section('content')
<script>
  const sendMessageRoute = "{{ route('dashboard.messages.send') }}";
</script>

<div class="email-wrapper container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="row g-0">
      <!-- Navigation -->
      @include('content.dashboard.messages.partials.sidebar', [
        'unreadMessagesCount' => $unreadMessagesCount ?? null,
        'sentMessagesCount' => $sentMessagesCount ?? null,
      ])

      <!-- Messages List -->
      <div class="col-12 col-lg-9 email-list">
        <div class="card shadow-none border-0">
          <div class="card-body email-list-wrapper p-0">
            <!-- Search -->
            <div class="email-list-item d-flex align-items-center bg-lighter px-3 py-2">
              <div class="email-list-item-content ms-2 ms-sm-4 me-2 w-100">
                <div class="input-group input-group-merge">
                  <span class="input-group-text" id="basic-addon1"><i class="massag-icon ti tabler-search"></i></span>
                  <input type="text" class="form-control email-search" placeholder="{{ __('Search mail') }}">
                </div>
              </div>
            </div>
            <hr class="my-0">

            <!-- Messages -->
            @forelse($messages as $message)
              <div class="email-list-item d-flex align-items-center {{ $message->read ? '' : 'email-unread' }}"
                   data-message-id="{{ $message->id }}"
                   data-bs-toggle="modal"
                   data-bs-target="#messageModal{{ $message->id }}">
                <div class="email-list-item-content ms-2 ms-sm-4 me-2">
                  <span class="email-list-item-username me-2 h6">{{ $message->sender->name }}</span>
                  <span class="email-list-item-subject d-xl-inline-block d-block">
                    {{ $message->subject }}
                  </span>
                </div>
                <div class="email-list-item-meta ms-auto d-flex align-items-center">
                  <span class="email-list-item-time">{{ $message->created_at->format('M d') }}</span>
                  <div class="ms-3">
                    @if($message->is_important)
                      <i class="massag-icon ti tabler-star text-warning"></i>
                    @endif
                  </div>
                </div>
              </div>


              <hr class="my-0">
            @empty
              @include('content.dashboard.messages.partials.empty-state', [
                'icon' => 'tabler-inbox',
                'title' => __('Your inbox is empty'),
                'subtitle' => __('No messages found')
              ])
            @endforelse

            @if(method_exists($messages, 'links'))
              <div class="d-flex justify-content-center my-3">
                {{ $messages->withQueryString()->links('components.pagination.custom') }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Message Modals -->
  @foreach($messages as $message)
    <div class="modal fade message-modal" id="messageModal{{ $message->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="massag-icon ti tabler-mail me-2"></i>
              {{ $message->subject }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="message-info d-flex align-items-center">
              <div class="d-flex align-items-center flex-grow-1">
                <img src="{{ $message->sender->getAvatarUrl() }}" alt="{{ $message->sender->name }}" class="sender-avatar">
                <div>
                  <h6 class="sender-name mb-0">{{ $message->sender->name }}</h6>
                  <div class="message-timestamp">
                    <i class="massag-icon ti tabler-calendar me-1"></i>
                    {{ $message->created_at->format('Y-m-d') }}
                    <i class="massag-icon ti tabler-clock ms-2 me-1"></i>
                    {{ $message->created_at->format('H:i') }}
                  </div>
                </div>
              </div>
              @if($message->is_important)
                <div class="ms-auto">
                  <span class="badge bg-warning">
                    <i class="massag-icon ti tabler-star me-1"></i>
                    {{ __('Important') }}
                  </span>
                </div>
              @endif
            </div>
            <div class="message-content">
              {!! $message->body !!}
            </div>

            <!-- Quick Reply Section -->
            <div class="message-reply">
              <div class="message-reply-header">
                <span class="message-reply-toggle">
                  <i class="massag-icon ti tabler-arrow-back-up me-1"></i>
                  {{ __('Quick Reply') }}
                </span>
              </div>
              <div class="message-reply-form">
                <form class="quick-reply-form" action="{{ route('dashboard.messages.send') }}" method="POST">
                  @csrf
                  <input type="hidden" name="recipient" value="{{ $message->sender_id }}">
                  <input type="hidden" name="subject" value="Re: {{ $message->subject }}">
                  <div id="editor{{ $message->id }}" class="message-reply-editor"></div>
                  <input type="hidden" name="message" class="quick-reply-message-input" value="">
                  <div class="invalid-feedback d-block quick-reply-error" style="display:none;">
                    {{ __('The message field is required.') }}
                  </div>
                  <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary quick-reply-btn">
                      <i class="massag-icon ti tabler-send me-1"></i>
                      {{ __('Send Reply') }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="massag-icon ti tabler-x me-1"></i>
              {{ __('Close') }}
            </button>
            <button type="button" class="btn btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $message->id }}">
              <i class="massag-icon ti tabler-trash me-1"></i>
              {{ __('Delete') }}
            </button>
            <a href="{{ route('dashboard.messages.compose', ['reply_to' => $message->id]) }}"
               class="btn btn-primary btn-reply">
              <i class="massag-icon ti tabler-edit me-1"></i>
              {{ __('Full Reply') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  @endforeach

</div>

{{-- Compose Modal --}}
@include('content.dashboard.messages.partials.compose-modal', ['users' => $users ?? []])

<!-- Delete Confirmation Modals -->
@foreach($messages as $message)
  <div class="modal modal-danger fade" id="deleteModal{{ $message->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm">
      <div class="modal-content">
        <div class="modal-header flex-column">
          <div class="icon-box">
            <i class="massag-icon ti tabler-alert-triangle text-warning" style="font-size: 3rem;"></i>
          </div>
          <h4 class="modal-title w-100 text-center mt-2">{{ __('Confirm Delete') }}</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center p-4">
          <p class="mb-0">{{ __('Are you sure you want to delete this message?') }}</p>
          <p class="text-muted small">{{ __('This action cannot be undone.') }}</p>
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
            <i class="massag-icon ti tabler-x me-1"></i>
            {{ __('Cancel') }}
          </button>
          <button type="button" class="btn btn-danger px-4 confirm-delete" data-message-id="{{ $message->id }}">
            <i class="massag-icon ti tabler-trash me-1"></i>
            {{ __('Delete') }}
          </button>
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection

<style>
.modal-confirm {
  max-width: 400px;
}
.modal-confirm .modal-header {
  border-bottom: none;
  position: relative;
}
.modal-confirm .modal-content {
  border-radius: 1rem;
}
.modal-confirm .icon-box {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  border-radius: 50%;
  background: rgba(255, 62, 29, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-confirm .icon-box i {
  color: #ff3e1d;
}
.modal-confirm .modal-header .btn-close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
}
.modal-confirm .modal-title {
  color: #ff3e1d;
  font-weight: 500;
}
</style>
