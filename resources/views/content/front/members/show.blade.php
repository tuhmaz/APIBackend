@php
$configData = Helper::appClasses();
use Illuminate\Support\Str;
use Detection\MobileDetect;

$detect = new MobileDetect;
$database = session('database', 'jo');
@endphp

@extends('layouts/layoutFront')

@section('title', $user->name . ' - عضو الفريق')

@section('content')
<section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
    <!-- Background Pattern -->
    <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

    <!-- Animated Shapes -->
    <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
    <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 text-center">

            </div>
        </div>
    </div>

    <!-- Wave Shape Divider -->
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #ffffff;"></path>
        </svg>
    </div>
</section>

<!-- Breadcrumb Section -->
<section class="breadcrumb-section py-3">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('home') }}">
                        <i class="member-icon ti tabler-home-check me-1"></i>{{ __('Home') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('front.members', ['database' => $database]) }}">
                        {{ __('Members') }}
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ Str::limit($user->name, 20) }}
                </li>
            </ol>
        </nav>
        <div class="progress mt-2">
            <div class="progress-bar" role="progressbar" style="width: 75%"></div>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section class="team-member-profile py-5">
    <div class="container">
        <div class="row">
            <!-- Profile Card -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card h-100 shadow-sm">
                    <div class="profile-header text-center py-4">
                        <div class="avatar-wrapper position-relative mb-3">
                            <img src="{{ $user->profile_photo_url }}"
                                 alt="{{ $user->name }}"
                                 class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>

                        @if($user->roles->isNotEmpty())
                        <div class="roles-container mb-3 ">
                            @foreach($user->roles as $role)
                            <div class="d-flex align-items-center justify-content-center">
                                <h3 class="mt-0 mb-1 me-2">{{ __($role->name) }}</h3>
                                @if($user->email_verified_at)
                                <span class="verified-badge text-primary align-items-center justify-content-center" data-bs-toggle="tooltip" title="حساب موثق">
                                    <i class="member-icon ti tabler-check"></i>
                                </span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($user->job_title)
                        <p class="text-muted mb-3">{{ $user->job_title }}</p>
                        @endif

                        @if($user->last_seen)
                        <div class="activity-status">
                            <div class="d-flex align-items-center justify-content-center">
                                @if($user->isOnline())
                                <span class="online-indicator me-2"></span>
                                <span class="text-success">متصل الآن</span>
                                @else
                                <span class="offline-indicator me-2"></span>
                                <span>آخر ظهور {{ $user->last_seen->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="profile-details p-4">
                        @if($user->roles && $user->roles->isNotEmpty())
                        <div class="detail-item mb-3">
                            <h6 class="detail-label d-flex align-items-center">
                                <i class="member-icon ti tabler-user-tag me-2"></i>
                                <span>الأدوار</span>
                            </h6>
                            <div class="roles-list d-flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                <span class="badge bg-primary">{{ __($role->name) }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(!empty($user->social_links))
                        <div class="detail-item mb-3">
                            <h6 class="detail-label d-flex align-items-center">
                                <i class="member-icon ti tabler-share me-2"></i>
                                <span>وسائل التواصل الاجتماعي</span>
                            </h6>
                            <div class="social-links d-flex flex-wrap gap-2">
                                @foreach((is_string($user->social_links) ? json_decode($user->social_links, true) : $user->social_links) as $platform => $url)
                                <a href="{{ $url }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="member-icon ti tabler-brand-{{ strtolower($platform) }}"></i>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($user->bio)
                        <div class="detail-item mb-3">
                            <h6 class="detail-label d-flex align-items-center">
                                <i class="member-icon ti tabler-info-circle me-2"></i>
                                <span>نبذة</span>
                            </h6>
                            <p class="detail-content">{{ $user->bio }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Information Section -->
            <div class="col-lg-8">
                <div class="card info-section-card shadow-sm mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="member-icon ti tabler-user-circle me-2"></i>المعلومات الشخصية</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <!-- الاسم الكامل -->
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center mb-2">
                                    <i class="member-icon ti tabler-user text-primary me-2"></i>
                                    <span class="fw-medium">الاسم الكامل:</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light border-0" value="{{ $user->name }}" disabled readonly>
                                </div>
                            </div>

                            <!-- الجنس -->
                            @if($user->gender)
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center mb-2">
                                    <i class="member-icon ti tabler-male-female text-primary me-2"></i>
                                    <span class="fw-medium">الجنس:</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light border-0" value="{{ $user->gender === 'male' ? 'ذكر' : 'أنثى' }}" disabled readonly>
                                </div>
                            </div>
                            @endif

                            <!-- البلد -->
                            @if($user->country)
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center mb-2">
                                    <i class="member-icon ti tabler-map-pin text-primary me-2"></i>
                                    <span class="fw-medium">البلد:</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light border-0" value="{{ $user->country }}" disabled readonly>
                                </div>
                            </div>
                            @endif

                            <!-- البريد الإلكتروني -->
                            @if($user->email)
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center mb-2">
                                    <i class="member-icon ti tabler-mail text-primary me-2"></i>
                                    <span class="fw-medium">البريد الإلكتروني:</span>
                                </label>
                                <div class="input-group">
                                    <input type="email" class="form-control bg-light border-0" value="{{ $user->email }}" disabled readonly>
                                </div>
                            </div>
                            @endif

                            <!-- رقم الهاتف -->
                            @if($user->phone)
                            <div class="col-md-6">
                                <label class="form-label d-flex align-items-center mb-2">
                                    <i class="member-icon ti tabler-phone text-primary me-2"></i>
                                    <span class="fw-medium">رقم الهاتف:</span>
                                </label>
                                <div class="input-group">
                                    <input type="tel" class="form-control bg-light border-0" value="{{ $user->phone }}" disabled readonly>
                                </div>
                            </div>
                            @endif




                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="card contact-form-card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-paper-plane me-2"></i>تواصل مع {{ $user->name }}</h4>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success mb-3" role="alert">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-check-circle me-2"></i>
                                        {{ session('success') }}
                                    </div>
                                    <small class="text-muted">{{ session('user_name') ? 'تم الإرسال بواسطة: ' . session('user_name') : '' }}</small>
                                </div>
                            </div>
                        @endif
                        <form id="memberContactForm" action="{{ route('front.members.contact', $user->id) }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">الاسم</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">الموضوع</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">الرسالة</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>
                                <div class="col-12">
                                    {!! NoCaptcha::display() !!}
                                    @error('g-recaptcha-response')
                                    <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-paper-plane me-2"></i>إرسال الرسالة
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection



{!! NoCaptcha::renderJs(app()->getLocale()) !!}
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Contact form submission
    $('#memberContactForm').submit(function(e) {
        e.preventDefault();
        let form = $(this);

        // Get hCaptcha response
        const hcaptchaResponse = grecaptcha.getResponse();
        if (!hcaptchaResponse) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'الرجاء التحقق من أنك لست روبوت',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الإرسال...');
            },
            success: function(response) {
                // Prevent the JSON response from showing
                event.preventDefault();

                // Show success message in the page
                $('#messageAlert').removeClass('d-none');
                $('#messageText').text(response.message);
                $('#messageUser').text('تم الإرسال بواسطة: ' + response.user_name);

                // Reset form and hCaptcha
                form[0].reset();
                grecaptcha.reset();

                // Hide message after 5 seconds
                setTimeout(function() {
                    $('#messageAlert').addClass('d-none');
                }, 5000);

                // Prevent default form submission
                return false;
            },
            error: function(xhr) {
                event.preventDefault();

                // Get error message from response
                let errorMessage = xhr.responseJSON ?
                    (xhr.responseJSON.message || 'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.') :
                    'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.';

                // Show error message in the page
                $('#messageAlert')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .removeClass('d-none');
                $('#messageText').text(errorMessage);
                $('#messageUser').text('');

                // Reset form and hCaptcha
                form[0].reset();
                grecaptcha.reset();

                // Hide message after 5 seconds
                setTimeout(function() {
                    $('#messageAlert').addClass('d-none');
                }, 5000);

                // Prevent default form submission
                return false;
            },
            complete: function() {
                // Re-enable the submit button
                form.find('button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-paper-plane me-2"></i>إرسال الرسالة');
            },
            error: function(xhr) {
                event.preventDefault();

                // Get error message from response
                let errorMessage = xhr.responseJSON ?
                    (xhr.responseJSON.message || 'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.') :
                    'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.';

                // Show error message in the page
                $('#messageAlert')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .removeClass('d-none');
                $('#messageText').text(errorMessage);
                $('#messageUser').text('');

                // Reset form and hCaptcha
                form[0].reset();
                grecaptcha.reset();

                // Hide message after 5 seconds
                setTimeout(function() {
                    $('#messageAlert').addClass('d-none');
                }, 5000);
            },
            complete: function() {
                // Re-enable the submit button
                form.find('button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-paper-plane me-2"></i>إرسال الرسالة');
            },
            error: function(xhr) {
                event.preventDefault();

                // Get error message from response
                let errorMessage = xhr.responseJSON ?
                    (xhr.responseJSON.message || 'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.') :
                    'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.';

                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: errorMessage,
                    confirmButtonColor: '#dc3545'
                });
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء الإرسال، الرجاء المحاولة مرة أخرى.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: errorMessage,
                    confirmButtonColor: '#dc3545'
                });
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-paper-plane me-2"></i>إرسال الرسالة');
            }
        });
    });
});
</script>
