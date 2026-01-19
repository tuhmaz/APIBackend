@extends('layouts/layoutFront')

@section('title', __('Documentation'))

@section('content')
<div class="container py-5">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-style2">
      <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ __('Documentation') }}</li>
    </ol>
  </nav>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Documentation') }}</h5>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">{{ __('Find guides, FAQs, and how-to articles about the platform.') }}</p>

          <h6 class="mt-3">{{ __('Getting Started') }}</h6>
          <ul class="list-unstyled mb-4">
            <li class="mb-2"><i class="ti tabler-circle-check text-success me-2"></i>{{ __('Account creation and login') }}</li>
            <li class="mb-2"><i class="ti tabler-circle-check text-success me-2"></i>{{ __('Navigating the dashboard') }}</li>
            <li class="mb-2"><i class="ti tabler-circle-check text-success me-2"></i>{{ __('Uploading files and managing content') }}</li>
          </ul>

          <h6 class="mt-3">{{ __('FAQ') }}</h6>
          <div class="accordion" id="docsFaq">
            <div class="accordion-item">
              <h2 class="accordion-header" id="q1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="true" aria-controls="a1">
                  {{ __('How do I reset my password?') }}
                </button>
              </h2>
              <div id="a1" class="accordion-collapse collapse show" aria-labelledby="q1" data-bs-parent="#docsFaq">
                <div class="accordion-body">{{ __('Use the Forgot Password link on the login page.') }}</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header" id="q2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">
                  {{ __('Where can I find my downloads?') }}
                </button>
              </h2>
              <div id="a2" class="accordion-collapse collapse" aria-labelledby="q2" data-bs-parent="#docsFaq">
                <div class="accordion-body">{{ __('Go to your profile or dashboard files section.') }}</div>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <a href="{{ route('support') }}" class="btn btn-primary">
              <i class="ti tabler-help me-1"></i>{{ __('Need more help? Contact Support') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
