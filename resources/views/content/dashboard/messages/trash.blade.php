@extends('layouts/contentNavbarLayout')

@section('title', __('Trash'))

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
  'resources/assets/vendor/libs/typeahead-js/typeahead.scss',
  'resources/assets/vendor/scss/pages/app-email.scss',
  'resources/assets/vendor/libs/quill/typography.scss',
  'resources/assets/vendor/libs/quill/editor.scss'
])
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
@endsection

@section('content')
<div class="email-wrapper container-xxl flex-grow-1 container-p-y">
  <div class="row g-0">
    @include('content.dashboard.messages.partials.sidebar', [
      'unreadMessagesCount' => $unreadMessagesCount ?? null,
      'sentMessagesCount' => $sentMessagesCount ?? null,
    ])

    <div class="col-12 col-lg-9 email-list">
      <div class="card shadow-none border-0">
        <div class="card-body email-list-wrapper p-0">
          @include('content.dashboard.messages.partials.list-trash', [
            'unreadMessages' => $unreadMessages ?? collect(),
            'sentMessages' => $sentMessages ?? collect(),
          ])
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Compose Modal --}}
@include('content.dashboard.messages.partials.compose-modal', ['users' => $users ?? []])
@endsection
