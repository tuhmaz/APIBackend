@php
$configData = Helper::appClasses();
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Detection\MobileDetect;
$detect = new MobileDetect;
@endphp

@extends('layouts/layoutFront')

@section('title', 'أعضاء الفريق')

@push('styles')
<style>
    /* Member Card Styles */
    .member-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        background: #fff;
    }

    .member-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Member Image Container */
    .member-img-container {
        border-radius: 50%;
        overflow: hidden;
        position: relative;
        background: #f8f9fa;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .member-profile-img {
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .member-img-container:hover .member-profile-img {
        transform: scale(1.05);
    }

    /* Status Badge */
    .status-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        z-index: 2;
    }

    .status-badge i {
        font-size: 12px;
    }

    .status-badge.online {
        background-color: #28a745;
    }

    .status-badge.offline {
        background-color: #6c757d;
    }

    /* Social Media Overlay */
    .social-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(32, 44, 69, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .member-card:hover .social-overlay {
        opacity: 1;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        font-size: 16px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .social-link:hover {
        background: var(--bs-primary);
        transform: translateY(-3px);
    }

    /* Card Body */
    .card-body {
        padding: 1.5rem;
    }

    .card-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .verified-badge {
        color: #3498db;
        font-size: 1rem;
    }

    /* Roles */
    .roles-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .role-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: rgba(40, 106, 173, 0.1);
        color: #286aad;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.2;
    }

    /* Bio */
    .member-bio {
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 1.25rem;
        line-height: 1.5;
    }

    /* View Profile Button */
    .view-profile-btn {
        border-radius: 50px;
        padding: 0.4rem 1.25rem;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    /* Empty State */
    .empty-state {
        background: #fff;
        border-radius: 12px;
        padding: 2.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .empty-state-icon {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1.5rem;
    }

    /* Responsive Adjustments */
    @media (max-width: 767.98px) {
        .member-card {
            max-width: 320px;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>
@endpush

@section('content')

<!-- Hero Section -->
<section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
    <!-- Background Pattern -->
    <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

    <!-- Animated Shapes -->
    <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
    <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8 text-center">
                <h2 class="display-6 text-white mb-3" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    فريقنا المتميز
                </h2>
                <p class="lead text-white-50 mb-4">تعرف على أعضاء فريقنا المحترفين وخبراتهم المتنوعة</p>

                <!-- Search and Filter -->
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4">
                        <form action="{{ route('front.members') }}" method="GET" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text"
                                           name="search"
                                           class="form-control form-control-lg border-start-0"
                                           placeholder="ابحث عن عضو..."
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="role" class="form-select form-select-lg" onchange="this.form.submit()">
                                    <option value="">جميع الأدوار</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                            {{ __($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('front.members') }}" class="btn btn-outline-light btn-lg w-100">
                                    <i class="fas fa-sync-alt me-1"></i> إعادة تعيين
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
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

<!-- Breadcrumb -->
<div class="container px-4 mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-style2">
            <li class="breadcrumb-item">
                <a href="{{ route('home') }}" class="text-muted">
                    <i class="member-icon ti tabler-home-check"></i> {{ __('home') }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                {{ __('Members') }}
            </li>
        </ol>
    </nav>
    <div class="progress mt-2">
        <div class="progress-bar" role="progressbar" style="width: 100%;"></div>
    </div>
</div>

<!-- Members Section -->
<section class="section-py bg-body">
    <div class="container">
        <!-- Members Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
            @forelse($users as $user)
                @php
                    // Get social links with fallback
                    $socialLinks = is_array($user->social_links) ? $user->social_links : (is_string($user->social_links) ? json_decode($user->social_links, true) : []);
                    $socialPlatforms = ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'];
                @endphp

                <div class="col">
                    <div class="card h-100 member-card">
                        <!-- Profile Image with Hover Effect -->
                        <div class="member-img-container position-relative mx-auto" style="width: 150px; height: 150px;">
                            <img src="{{ $user->profile_photo_url }}"
                                 class="member-profile-img w-100 h-100 rounded-circle border"
                                 alt="{{ $user->name }}"
                                 loading="lazy">

                            <!-- Online Status Badge -->
                            <div class="status-badge {{ $user->isOnline() ? 'online' : 'offline' }}"
                                 data-bs-toggle="tooltip"
                                 data-bs-placement="top"
                                 title="{{ $user->isOnline() ? 'متصل الآن' : 'غير متصل' }}">
                                <i class="fas fa-circle"></i>
                            </div>

                            <!-- Social Media Hover Overlay -->
                            <div class="social-overlay rounded-circle">
                                <div class="social-links d-flex align-items-center justify-content-center h-100">
                                    @foreach($socialPlatforms as $platform)
                                        @if(!empty($socialLinks[$platform]))
                                            <a href="{{ $socialLinks[$platform] }}"
                                               target="_blank"
                                               class="social-link mx-1"
                                               data-bs-toggle="tooltip"
                                               data-bs-placement="top"
                                               title="{{ ucfirst($platform) }}">
                                                <i class="fab fa-{{ $platform }}"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Member Info -->
                        <div class="card-body text-center p-4">
                            <!-- Name with Verification Badge -->
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <h5 class="card-title mb-0">{{ $user->name }}</h5>
                                @if($user->email_verified_at)
                                    <span class="verified-badge ms-2" data-bs-toggle="tooltip" title="حساب موثّق">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                @endif
                            </div>

                            <!-- Roles -->
                            @if($user->roles->isNotEmpty())
                                <div class="roles-container mb-3">
                                    @foreach($user->roles as $role)
                                        <span class="role-badge">{{ __($role->name) }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Bio -->
                            @if($user->bio)
                                <p class="member-bio">{{ Str::limit($user->bio, 80) }}</p>
                            @endif

                            <!-- View Profile Button -->
                            <a href="{{ route('front.members.show', $user->id) }}"
                               class="btn btn-outline-primary btn-sm mt-3 view-profile-btn">
                                <i class="fas fa-user-circle me-1"></i> عرض الملف الشخصي
                            </a>
                        </div>

                        <!-- Footer with Additional Info -->
                        <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                            <!-- Last Seen -->
                            @if(!$user->isOnline() && $user->last_seen)
                                <div class="last-seen text-muted small">
                                    <i class="far fa-clock me-1"></i>
                                    آخر ظهور: {{ $user->last_seen->diffForHumans() }}
                                </div>
                            @endif

                            <!-- Social Media Icons (Visible on Mobile) -->
                            <div class="d-md-none mt-3">
                                <div class="d-flex justify-content-center gap-3">
                                    @foreach($socialPlatforms as $platform)
                                        @if(!empty($socialLinks[$platform]))
                                            <a href="{{ $socialLinks[$platform] }}"
                                               target="_blank"
                                               class="text-muted hover-primary"
                                               data-bs-toggle="tooltip"
                                               title="{{ ucfirst($platform) }}">
                                                <i class="fab fa-{{ $platform }} fa-lg"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="empty-state text-center py-5">
                        <div class="empty-state-icon">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <h4 class="mt-3">لا يوجد أعضاء لعرضهم حالياً</h4>
                        <p class="text-muted mb-4">لم يتم العثور على أعضاء متطابقين مع معايير البحث</p>
                        <a href="{{ route('front.members') }}" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i> إعادة تحميل القائمة
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="row mt-5">
                <div class="col-12 d-flex justify-content-center">
                    <nav aria-label="Page navigation">
                        {{ $users->withQueryString()->links('components.pagination.custom') }}
                    </nav>
                </div>
            </div>
        @endif
    </div>
</section>

<style>
    .member-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        overflow: hidden;
    }

    .member-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .member-avatar-wrapper {
        width: 180px;
        height: 180px;
        margin: 0 auto;
        margin-top: -40px;
        position: relative;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .member-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .hover-primary:hover {
        color: #286aad !important;
    }

    .bg-primary-light {
        background-color: rgba(40, 106, 173, 0.1);
    }
</style>

@endsection
