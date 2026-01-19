@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
// Default fallback logo path for authentication views
$defaultLogo = 'assets/img/logo/default-logo.webp';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
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
        <img src="{{ asset('assets/img/illustrations/auth-login-illustration-' . $configData['theme'] . '.png') }}"
          alt="auth-login-cover" class="my-5 auth-illustration"
          data-app-light-img="illustrations/webp/auth-login-illustration-light.webp"
          data-app-dark-img="illustrations/webp/auth-login-illustration-dark.webp" />
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
          alt="auth-login-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png"
          data-app-dark-img="illustrations/bg-shape-image-dark.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Login -->
    <div class="d-flex col-12 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-12 pt-5">
        <h4 class="mb-1">{{ __('Welcome to') }} {{ config('settings.site_name') }}! </h4>
        <p class="mb-6">{{ __('Please sign-in to your account and start') }}</p>

        @if (session('status'))
        <div class="alert alert-success mb-1 rounded-0" role="alert">
          <div class="alert-body">
            {{ session('status') }}
          </div>
        </div>
        @endif
        <form id="formAuthentication" class="mb-6" action="{{ route('login.submit') }}" method="POST">
          @csrf
          <div class="mb-6">
            <label for="login-email" class="form-label">{{ __('Email') }}</label>
            <input type="text" class="form-control @error('email') is-invalid @enderror" id="login-email" name="email"
              placeholder="john@example.com" autofocus value="{{ old('email') }}" />
            @error('email')
            <span class="invalid-feedback" role="alert">
              <span class="fw-medium">{{ $message }}</span>
            </span>
            @enderror
          </div>
          <div class="mb-6 form-password-toggle">
            <label class="form-label" for="login-password">{{ __('Password') }}</label>
            <div class="input-group input-group-merge @error('password') is-invalid @enderror">
              <input type="password" id="login-password" class="form-control @error('password') is-invalid @enderror"
                name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                aria-describedby="password" />
              <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
            </div>
            @error('password')
            <span class="invalid-feedback" role="alert">
              <span class="fw-medium">{{ $message }}</span>
            </span>
            @enderror
          </div>
          <div class="my-8">
            <div class="d-flex justify-content-between">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input" type="checkbox" id="remember-me" name="remember"
                  {{ old('remember') ? 'checked' : '' }} />
                <label class="form-check-label" for="remember-me"> {{ __('Remember Me') }} </label>
              </div>
              @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}">
                <p class="mb-0">{{ __('Forgot Password?') }}</p>
              </a>
              @endif
            </div>
          </div>
          <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Sign in') }}</button>
        </form>

        <p class="text-center">
          <span>{{ __('New on our platform?') }}</span>
          @if (Route::has('register'))
          <a href="{{ route('register') }}">
            <span>{{ __('Create an account') }}</span>
          </a>
          @endif
        </p>
        <div class="divider my-6">
          <div class="divider-text">or</div>
        </div>

        <div class="d-flex justify-content-center">
           <div class="d-grid mb-3">
          <a href="{{ route('login.google') }}" class="btn btn-outline-danger">
            <i class="tf-icons fa-brands fa-google me-2"></i>
            {{ __('Sign in with Google') }}
          </a>
        </div>

        </div>
      </div>
    </div>
    <!-- /Login -->
  </div>
</div>
@endsection
