@php
    use Illuminate\Support\Str;
    // Use the viewed user's profile photo accessor for correct URL handling
    $profilePhoto = isset($user) ? $user->profile_photo_url : null;
    $randomAvatarNumber = rand(1, 5);
    $defaultAvatar = 'assets/img/avatars/' . $randomAvatarNumber . '.png';
@endphp
@extends('layouts/contentNavbarLayout')

@section('title', __('User Profile'))

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">{{ __('User Management') }} /</span> {{ __('User Profile') }}
</h4>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <h5 class="card-header">{{ __('User Profile') }}: {{ $user->name }}</h5>
            <div class="card-body">
                <div class="d-flex align-items-start align-items-sm-center gap-4 mb-4">
                    <img src="{{ $profilePhoto ?? asset($defaultAvatar) }}"
                         alt="user-avatar"
                         class="d-block rounded img-fluid"
                         style="max-width: 80px; max-height: 80px; object-fit: cover;"
                         id="uploadedAvatar" />
                </div>
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <!-- User Pills -->
                        <ul class="nav nav-pills flex-column flex-md-row mb-3">
                            <li class="nav-item">
                                <a class="nav-link active" href="javascript:void(0);">
                                    <i class="user-icon ti tabler-user-check ti-xs me-1"></i> {{ __('Details') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('dashboard.users.edit', $user) }}">
                                    <i class="user-icon ti tabler-user-plus ti-xs me-1"></i> {{ __('Edit') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                @can('manage permissions')
                                <a class="nav-link" href="{{ route('dashboard.users.permissions-roles', $user) }}">
                                    <i class="user-icon ti tabler-lock ti-xs me-1"></i> {{ __('Roles & Permissions') }}
                                </a>
                                @endcan
                            </li>
                        </ul>
                        <!--/ User Pills -->

                        <!-- User Details -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-start align-items-sm-center gap-4">
                                    <div class="button-wrapper">
                                        <a href="{{ route('dashboard.users.edit', $user) }}" class="btn btn-primary me-2">
                                            <i class="user-icon ti tabler-edit me-1"></i>
                                            {{ __('Edit') }}
                                        </a>
                                        @can('manage permissions')
                                        <a href="{{ route('dashboard.users.permissions-roles', $user) }}" class="btn btn-primary me-2">
                                            <i class="user-icon ti tabler-lock me-1"></i>
                                            {{ __('Roles & Permissions') }}
                                        </a>
                                        @endcan
                                        <form action="{{ route('dashboard.users.destroy', $user) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger me-2" onclick="return confirm('{{ __('Are you sure you want to delete this user?') }}')">
                                                <i class="user-icon ti tabler-trash me-1"></i>
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Username -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Username') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->name }}" readonly />
                                    </div>
                                    <!-- Email -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Email') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->email }}" readonly />
                                    </div>
                                    <!-- Status -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Status') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->email_verified_at ? __('Active') : __('Pending') }}" readonly />
                                    </div>
                                    <!-- Role -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Role') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->roles->pluck('name')->implode(', ') }}" readonly />
                                    </div>
                                    <!-- Phone Number -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Phone Number') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->phone ?? '-' }}" readonly />
                                    </div>
                                    <!-- Job Title -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Job Title') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->job_title ?? '-' }}" readonly />
                                    </div>
                                    <!-- Country -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Country') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->country ?? '-' }}" readonly />
                                    </div>
                                    <!-- Gender -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Gender') }}</label>
                                        @php
                                            $genderMap = ['male' => __('Male'), 'female' => __('Female')];
                                            $genderLabel = $user->gender ? ($genderMap[$user->gender] ?? $user->gender) : '-';
                                        @endphp
                                        <input class="form-control" type="text" value="{{ $genderLabel }}" readonly />
                                    </div>
                                    <!-- Member Since -->
                                    <div class="mb-3 col-md-6">
                                        <label class="form-label">{{ __('Member Since') }}</label>
                                        <input class="form-control" type="text" value="{{ $user->created_at->format('F j, Y') }}" readonly />
                                    </div>
                                    <!-- Bio -->
                                    <div class="mb-3 col-12">
                                        <label class="form-label">{{ __('Bio') }}</label>
                                        <textarea class="form-control" rows="3" readonly>{{ $user->bio ?? '-' }}</textarea>
                                    </div>
                                    <!-- Social Links -->
                                    @php $links = is_array($user->social_links) ? $user->social_links : []; @endphp
                                    <div class="mb-3 col-12">
                                        <label class="form-label">{{ __('Social Links') }}</label>
                                        @if(!empty($links))
                                            <ul class="list-unstyled mb-0">
                                                @foreach(['facebook','twitter','instagram','linkedin','youtube'] as $net)
                                                    @if(!empty($links[$net]))
                                                        <li class="mb-1">
                                                            <a href="{{ $links[$net] }}" target="_blank" rel="noopener noreferrer">{{ Str::ucfirst($net) }}</a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <input class="form-control" type="text" value="-" readonly />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--/ User Details -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
