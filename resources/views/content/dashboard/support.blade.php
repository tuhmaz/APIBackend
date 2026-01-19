@extends('layouts.contentNavbarLayout')

@section('title', __('Support'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Support Center') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">{{ __('We are here to help. Browse common topics or contact us directly.') }}</p>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <h6 class="mb-2"><i class="ti tabler-lock me-1"></i>{{ __('Account & Security') }}</h6>
                <p class="text-muted mb-0">{{ __('Password, login, and privacy settings.') }}</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <h6 class="mb-2"><i class="ti tabler-file-description me-1"></i>{{ __('Content & Files') }}</h6>
                <p class="text-muted mb-0">{{ __('Uploading, organizing, and downloading files.') }}</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <h6 class="mb-2"><i class="ti tabler-bell me-1"></i>{{ __('Notifications') }}</h6>
                <p class="text-muted mb-0">{{ __('Manage your alerts and subscriptions.') }}</p>
              </div>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">{{ __('Contact Support') }}</h6>
          <form method="post" action="{{ route('front.members.contact', ['id' => auth()->id() ?? 0]) }}">
            @csrf
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">{{ __('Your Name') }}</label>
                <input type="text" class="form-control" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required>
              </div>
              <div class="col-12">
                <label class="form-label">{{ __('Subject') }}</label>
                <input type="text" class="form-control" name="subject" required>
              </div>
              <div class="col-12">
                <label class="form-label">{{ __('Message') }}</label>
                <textarea class="form-control" rows="5" name="message" required></textarea>
              </div>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary"><i class="ti tabler-mail me-1"></i>{{ __('Send') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
