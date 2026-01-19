@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
// Default fallback logo path for authentication views
$defaultLogo = 'assets/img/logo/default-logo.webp';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Verify Email'))

@section('page-style')
<!-- Page -->
@vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
    <span class="app-brand-logo">
      <img
        src="{{ config('settings.site_logo') ? asset('storage/' . config('settings.site_logo')) : asset($defaultLogo) }}"
        alt="{{ config('settings.site_name', 'Site') }} Logo"
        width="45"
        height="45"
        loading="lazy" />
    </span>
    <span class="app-brand-text" style="color: #223553;">{{ config('settings.site_name', config('app.name')) }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
    <!-- /Left Text -->
    <div class="d-none d-xl-flex col-xl-8 p-0">
      <div class="auth-cover-bg d-flex justify-content-center align-items-center">
        <img
          src="{{ asset('assets/img/illustrations/auth-verify-email-illustration-' . $configData['theme'] . '.png') }}"
          alt="auth-verify-email-cover" class="my-5 auth-illustration"
          data-app-light-img="illustrations/auth-verify-email-illustration-light.png"
          data-app-dark-img="illustrations/auth-verify-email-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
          alt="auth-verify-email-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!--  Verify email -->
    <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-6 p-sm-12">
      <div class="w-px-400 mx-auto mt-12 mt-5">
        <h4 class="mb-1">{{ __('Verify your email') }} ✉️</h4>

        @if (session('status') == 'verification-link-sent')
          <div class="alert alert-success" role="alert">
            {{ __('A new verification link has been sent to your email address.') }}
          </div>
        @endif

        <p class="text-start mb-3">
          {{ __('Account activation link sent to your email address:') }}
          <span class="fw-medium">{{ optional(auth()->user())->email }}</span>.
          {{ __('Please follow the link inside to continue.') }}
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="d-grid gap-3">
          @csrf
          <button type="submit" class="btn btn-primary w-100">{{ __('Resend Verification Email') }}</button>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-4">
          <a class="btn btn-outline-secondary" href="{{ url('/') }}">{{ __('Skip for now') }}</a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-text text-danger p-0">{{ __('Log out') }}</button>
          </form>
        </div>
      </div>
    </div>
    <!-- / Verify email -->
  </div>
</div>
@endsection
