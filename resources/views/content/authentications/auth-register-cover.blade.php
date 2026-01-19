@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
// Default fallback logo path for authentication views
$defaultLogo = 'assets/img/logo/default-logo.webp';
use Illuminate\Support\Facades\Route;
use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
@endphp

@extends('layouts/blankLayout')

@section('title', 'Register Cover - Pages')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
{{-- Temporarily disable FormValidation vendor scripts on register page to avoid runtime error --}}
{{-- @vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js']) --}}
{!! NoCaptcha::renderJs(app()->getLocale(), null, ['useRecaptchaNet' => true, 'defer' => true, 'async' => true]) !!}
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
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
        <img src="{{ asset('assets/img/illustrations/auth-register-illustration-' . $configData['theme'] . '.png') }}"
          alt="auth-register-cover" class="my-5 auth-illustration"
          data-app-light-img="illustrations/webp/auth-register-illustration-light.webp"
          data-app-dark-img="illustrations/webp/auth-register-illustration-dark.webp" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
          alt="auth-register-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Register -->
    <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-12 pt-5">
         <h4 class="mb-1">{{ __('Adventure starts here') }} {{ config('settings.site_name') }}!  </h4>
        <p class="mb-6">{{ __('Make your app management easy and fun!') }}</p>


            <form id="formAuthentication" data-no-fv="1" class="mb-6" action="{{ route('register.submit') }}" method="POST">
          @csrf
          <div class="mb-6">
            <label for="username" class="form-label">{{ __('Username') }}</label>
            <input type="text"
                   class="form-control @error('name') is-invalid @enderror"
                   id="username"
                   name="name"
                   placeholder="Enter your username"
                   value="{{ old('name') }}"
                   autofocus>
            @error('name')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
          </div>
          <div class="mb-6">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="text"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   placeholder="Enter your email"
                   value="{{ old('email') }}">
            @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
          </div>
          <div class="mb-6 form-password-toggle">
            <label class="form-label" for="password">{{ __('Password') }}</label>
            <div class="input-group input-group-merge">
              <input type="password"
                     id="password"
                     class="form-control @error('password') is-invalid @enderror"
                     name="password"
                     placeholder="{{ __('Enter your password') }}"
                     aria-describedby="password" />
              <span class="input-group-text cursor-pointer"><i class="alemedu-icon ti tabler-eye-off"></i></span>
            </div>
            @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
          </div>

          <div class="mb-6 form-password-toggle">
            <label class="form-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
            <div class="input-group input-group-merge">
              <input type="password"
                     id="password_confirmation"
                     class="form-control @error('password_confirmation') is-invalid @enderror"
                     name="password_confirmation"
                     placeholder="{{ __('Confirm your password') }}"
                     aria-describedby="password_confirmation" />
              <span class="input-group-text cursor-pointer"><i class="alemedu-icon ti tabler-eye-off"></i></span>
            </div>
            @error('password_confirmation')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
          </div>

          <div class="mb-6 mt-8">
            <div class="form-check mb-8 ms-2">
              <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms">
              <label class="form-check-label" for="terms-conditions">
                {{ __('I agree to') }}
                <a href="javascript:void(0);">{{ __('privacy policy & terms') }}</a>
              </label>
            </div>
          </div>

          {{-- reCAPTCHA --}}
          <div class="mb-3">
            {!! NoCaptcha::display(['lang' => 'ar']) !!}
            @if ($errors->has('g-recaptcha-response'))
                <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
            @endif
          </div>
          <div class="mb-3">
            <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Sign up') }}</button>
          </div>
        </form>

        <div class="divider my-4">
          <div class="divider-text">{{ __('or') }}</div>
        </div>

        <div class="d-grid mb-3">
          <a href="{{ route('login.google') }}" class="btn btn-outline-danger">
            <i class="tf-icons fa-brands fa-google me-2"></i>
            {{ __('Sign up with Google') }}
          </a>
        </div>

        <p class="text-center">
          <span>{{ __('Already have an account?') }}</span>
          <a href="{{ route('login') }}">
            <span>{{ __('Sign in instead') }}</span>
          </a>
        </p>


        </div>
      </div>
    </div>
    <!-- /Register -->
  </div>
</div>
@endsection
