<div class="modal fade" id="composeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="massag-icon ti tabler-mail me-2"></i> {{ __('Compose Message') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('dashboard.messages.send') }}" method="POST" id="compose-form-modal">
          @csrf

          @if($errors->any())
            <div class="alert alert-danger mb-3">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-3" role="alert">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="recipient-modal">{{ __('To') }}</label>
              <select id="recipient-modal" name="recipient" class="form-select @error('recipient') is-invalid @enderror" required>
                <option value="">{{ __('Select recipient') }}</option>
                @foreach(($users ?? []) as $user)
                  @if($user->id !== auth()->id())
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endif
                @endforeach
              </select>
              @error('recipient')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="subject-modal">{{ __('Subject') }}</label>
              <input type="text" id="subject-modal" name="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="{{ __('Message subject') }}" required>
              @error('subject')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="message-modal">{{ __('Message') }}</label>
              <textarea id="message-modal" name="message" class="form-control @error('message') is-invalid @enderror" placeholder="{{ __('Type your message here...') }}" rows="8" required></textarea>
              <div class="message-error invalid-feedback" style="display:none;">{{ __('The message field is required.') }}</div>
              @error('message')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <div>
          <button type="submit" form="compose-form-modal" class="btn btn-primary">
            <i class="massag-icon ti tabler-send me-1"></i> {{ __('Send') }}
          </button>
          <button type="button" class="btn btn-label-secondary" id="save-draft-btn">
            <i class="massag-icon ti tabler-file me-1"></i> {{ __('Save Draft') }}
          </button>
        </div>
        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">
          <i class="massag-icon ti tabler-trash me-1"></i> {{ __('Discard') }}
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('compose-form-modal');
    if (!form) return;
    const saveDraftBtn = document.getElementById('save-draft-btn');
    if (saveDraftBtn) {
      saveDraftBtn.addEventListener('click', function () {
        // Switch form action to draft route and submit
        form.setAttribute('action', "{{ route('dashboard.messages.draft.save') }}");
        form.submit();
      });
    }
    form.addEventListener('submit', function (e) {
      const val = (document.getElementById('message-modal')?.value || '').trim();
      const err = form.querySelector('.message-error');
      if (!val) {
        // Allow empty for drafts, block only for sending
        const isDraft = form.getAttribute('action') === "{{ route('dashboard.messages.draft.save') }}";
        if (!isDraft) {
          e.preventDefault();
          if (err) err.style.display = 'block';
          return;
        }
      }
      if (err) err.style.display = 'none';
    });
  });
</script>
