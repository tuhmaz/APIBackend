@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$defaultLogo = 'assets/img/logo/default-logo.webp';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Forgot Password'))

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
 <a href="{{url('/')}}" class="app-brand auth-cover-brand">
  <span class="app-brand-logo">

<img src="{{ isset($siteSettings['site_logo']) ? asset('storage/' . $siteSettings['site_logo']) : asset($defaultLogo) }}"
     alt="{{ config('settings.site_name') }} Logo"
     width="45"
     height="45"
     loading="lazy" />
</span>
<span class="app-brand-text"style="color: #223553;">{{ config('settings.site_name') }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
    <!-- /Left Text -->
    <div class="d-none d-xl-flex col-xl-8 p-0">
      <div class="auth-cover-bg d-flex justify-content-center align-items-center">
        <img
          src="{{ asset('assets/img/illustrations/auth-forgot-password-illustration-' . $configData['theme'] . '.png') }}"
          alt="auth-forgot-password-cover" class="my-5 auth-illustration d-lg-block d-none"
          data-app-light-img="illustrations/webp/auth-forgot-password-illustration-light.webp"
          data-app-dark-img="illustrations/webp/auth-forgot-password-illustration-dark.webp" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
          alt="auth-forgot-password-cover" class="platform-bg"
          data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Forgot Password -->
    <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-12 mt-5">
        <h4 class="mb-1">{{ __('Forgot Password?') }} 🔒</h4>
        <p class="mb-6">{{ __('Enter your email and we\'ll send you instructions to reset your password') }}</p>
        @if (session('status'))
        <div class="alert alert-success mb-1 rounded-0" role="alert">
          <div class="alert-body">
            {{ session('status') }}
          </div>
        </div>
        @endif
        <form id="formAuthentication" class="mb-6" action="{{ route('password.email') }}" method="POST">
          @csrf
          <div class="mb-6 form-control-validation">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="{{ __('Enter your email') }}" autofocus value="{{ old('email') }}" />
            @error('email')
            <span class="invalid-feedback" role="alert">
              <span class="fw-medium">{{ $message }}</span>
            </span>
            @enderror
          </div>
          <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Send Reset Link') }}</button>
        </form>
        <div class="text-center">
          <a href="{{ route('login') }}" class="d-flex justify-content-center">
            <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
            {{ __('Back to login') }}
          </a>
        </div>
      </div>
    </div>
    <!-- /Forgot Password -->
  </div>
</div>
@endsection
