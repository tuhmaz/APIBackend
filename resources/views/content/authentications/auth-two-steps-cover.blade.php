@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$defaultLogo = 'assets/img/logo/default-logo.webp';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Two Step Verification'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js', 'resources/assets/js/pages-auth-two-steps.js'])
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
    <span class="app-brand-text" style="color: #223553;">{{ config('settings.site_name') }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
    <!-- /Left Text -->
    <div class="d-none d-xl-flex col-xl-8 p-0">
      <div class="auth-cover-bg d-flex justify-content-center align-items-center">
        <img src="{{ asset('assets/img/illustrations/auth-two-step-illustration-' . $configData['theme'] . '.png') }}"
          alt="auth-two-steps-cover" class="my-5 auth-illustration"
          data-app-light-img="illustrations/auth-two-step-illustration-light.png"
          data-app-dark-img="illustrations/auth-two-step-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
          alt="auth-two-steps-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Two Steps Verification -->
    <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-6 p-sm-12">
      <div class="w-px-400 mx-auto mt-12 mt-5">
        <h4 class="mb-1">{{ __('Two Step Verification') }} 💬</h4>
        <p class="text-start mb-6">
          {{ __('We sent a verification code to your mobile. Enter the code from the mobile in the field below.') }}
          <span class="fw-medium d-block mt-1 text-heading">******1234</span>
        </p>
        <p class="mb-0">{{ __('Type your 6 digit security code') }}</p>
        <form id="twoStepsForm" action="{{ url()->current() }}" method="POST">
          @csrf
          <div class="mb-6 form-control-validation">
            <div class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper">
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" autofocus />
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" />
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" />
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" />
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" />
              <input type="tel" name="code[]" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" />
            </div>
            <!-- Create a hidden field which is combined by 3 fields above -->
            <input type="hidden" name="otp" />
            @error('otp')
            <span class="invalid-feedback d-block" role="alert">
              <span class="fw-medium">{{ $message }}</span>
            </span>
            @enderror
          </div>
          <button class="btn btn-primary d-grid w-100 mb-6" type="submit">{{ __('Verify my account') }}</button>
          <div class="text-center">
            {{ __('Didn\'t get the code?') }}
            <a href="javascript:void(0);"> {{ __('Resend') }} </a>
          </div>
        </form>
      </div>
    </div>
    <!-- /Two Steps Verification -->
  </div>
</div>
@endsection