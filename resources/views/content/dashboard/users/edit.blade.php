@php
    use Illuminate\Support\Str;
    // Always derive photo from the edited user, not the authenticated user
    $profilePhoto = isset($user) ? $user->profile_photo_url : null;
    $randomAvatarNumber = rand(1, 5);
    $defaultAvatar = 'assets/img/avatars/' . $randomAvatarNumber . '.png';
@endphp
@extends('layouts/contentNavbarLayout')

@section('title', __('Edit User'))

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">{{ __('User Management') }} /</span> {{ __('Edit User') }}
</h4>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <h5 class="card-header">{{ __('Edit User') }}: {{ $user->name }}</h5>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.users.update', $user) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Name -->
                        <div class="mb-3 col-md-6">
                            <label for="name" class="form-label">{{ __('Full Name') }}</label>
                            <input class="form-control @error('name') is-invalid @enderror" 
                                   type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3 col-md-6">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input class="form-control @error('email') is-invalid @enderror" 
                                   type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="mb-3 col-md-6">
                            <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
                            <input class="form-control @error('phone') is-invalid @enderror" 
                                   type="text" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" />
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Job Title -->
                        <div class="mb-3 col-md-6">
                            <label for="job_title" class="form-label">{{ __('Job Title') }}</label>
                            <input class="form-control @error('job_title') is-invalid @enderror" 
                                   type="text" 
                                   id="job_title" 
                                   name="job_title" 
                                   value="{{ old('job_title', $user->job_title) }}" />
                            @error('job_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gender -->
                        <div class="mb-3 col-md-6">
                            <label for="gender" class="form-label">{{ __('Gender') }}</label>
                            <select class="form-select @error('gender') is-invalid @enderror" 
                                    id="gender" 
                                    name="gender">
                                <option value="">{{ __('Select Gender') }}</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>{{ __('Male') }}</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>{{ __('Female') }}</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div class="mb-3 col-md-6">
                            <label for="country" class="form-label">{{ __('Country') }}</label>
                            <select class="form-select @error('country') is-invalid @enderror" 
                                    id="country" 
                                    name="country">
                                <option value="">{{ __('Select Country') }}</option>
                                <option value="المملكة العربية السعودية" {{ old('country', $user->country) == 'المملكة العربية السعودية' ? 'selected' : '' }}>المملكة العربية السعودية</option>
                                <option value="الإمارات العربية المتحدة" {{ old('country', $user->country) == 'الإمارات العربية المتحدة' ? 'selected' : '' }}>الإمارات العربية المتحدة</option>
                                <option value="مصر" {{ old('country', $user->country) == 'مصر' ? 'selected' : '' }}>مصر</option>
                                <option value="العراق" {{ old('country', $user->country) == 'العراق' ? 'selected' : '' }}>العراق</option>
                                <option value="الكويت" {{ old('country', $user->country) == 'الكويت' ? 'selected' : '' }}>الكويت</option>
                                <option value="قطر" {{ old('country', $user->country) == 'قطر' ? 'selected' : '' }}>قطر</option>
                                <option value="البحرين" {{ old('country', $user->country) == 'البحرين' ? 'selected' : '' }}>البحرين</option>
                                <option value="عمان" {{ old('country', $user->country) == 'عمان' ? 'selected' : '' }}>عمان</option>
                                <option value="اليمن" {{ old('country', $user->country) == 'اليمن' ? 'selected' : '' }}>اليمن</option>
                                <option value="سوريا" {{ old('country', $user->country) == 'سوريا' ? 'selected' : '' }}>سوريا</option>
                                <option value="لبنان" {{ old('country', $user->country) == 'لبنان' ? 'selected' : '' }}>لبنان</option>
                                <option value="الأردن" {{ old('country', $user->country) == 'الأردن' ? 'selected' : '' }}>الأردن</option>
                                <option value="فلسطين" {{ old('country', $user->country) == 'فلسطين' ? 'selected' : '' }}>فلسطين</option>
                                <option value="ليبيا" {{ old('country', $user->country) == 'ليبيا' ? 'selected' : '' }}>ليبيا</option>
                                <option value="تونس" {{ old('country', $user->country) == 'تونس' ? 'selected' : '' }}>تونس</option>
                                <option value="الجزائر" {{ old('country', $user->country) == 'الجزائر' ? 'selected' : '' }}>الجزائر</option>
                                <option value="المغرب" {{ old('country', $user->country) == 'المغرب' ? 'selected' : '' }}>المغرب</option>
                                <option value="موريتانيا" {{ old('country', $user->country) == 'موريتانيا' ? 'selected' : '' }}>موريتانيا</option>
                                <option value="السودان" {{ old('country', $user->country) == 'السودان' ? 'selected' : '' }}>السودان</option>
                                <option value="الصومال" {{ old('country', $user->country) == 'الصومال' ? 'selected' : '' }}>الصومال</option>
                                <option value="جيبوتي" {{ old('country', $user->country) == 'جيبوتي' ? 'selected' : '' }}>جيبوتي</option>
                                <option value="جزر القمر" {{ old('country', $user->country) == 'جزر القمر' ? 'selected' : '' }}>جزر القمر</option>
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Social Links -->
                        <div class="mb-3 col-md-6">
                            <label class="form-label">{{ __('Social Links') }}</label>
                            
                            @php
                                $socialLinks = is_array($user->social_links) ? $user->social_links : [];
                                $facebook = $socialLinks['facebook'] ?? '';
                                $twitter = $socialLinks['twitter'] ?? '';
                                $instagram = $socialLinks['instagram'] ?? '';
                                $linkedin = $socialLinks['linkedin'] ?? '';
                                $youtube = $socialLinks['youtube'] ?? '';
                            @endphp
                            
                            <div class="mb-2">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                    <input type="url" 
                                           class="form-control @error('social_links.facebook') is-invalid @enderror" 
                                           name="social_links[facebook]" 
                                           placeholder="https://facebook.com/username" 
                                           value="{{ old('social_links.facebook', $facebook) }}" />
                                </div>
                                @error('social_links.facebook')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-2">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                    <input type="url" 
                                           class="form-control @error('social_links.twitter') is-invalid @enderror" 
                                           name="social_links[twitter]" 
                                           placeholder="https://twitter.com/username" 
                                           value="{{ old('social_links.twitter', $twitter) }}" />
                                </div>
                                @error('social_links.twitter')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-2">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                    <input type="url" 
                                           class="form-control @error('social_links.instagram') is-invalid @enderror" 
                                           name="social_links[instagram]" 
                                           placeholder="https://instagram.com/username" 
                                           value="{{ old('social_links.instagram', $instagram) }}" />
                                </div>
                                @error('social_links.instagram')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-2">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                    <input type="url" 
                                           class="form-control @error('social_links.linkedin') is-invalid @enderror" 
                                           name="social_links[linkedin]" 
                                           placeholder="https://linkedin.com/in/username" 
                                           value="{{ old('social_links.linkedin', $linkedin) }}" />
                                </div>
                                @error('social_links.linkedin')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-2">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                    <input type="url" 
                                           class="form-control @error('social_links.youtube') is-invalid @enderror" 
                                           name="social_links[youtube]" 
                                           placeholder="https://youtube.com/username" 
                                           value="{{ old('social_links.youtube', $youtube) }}" />
                                </div>
                                @error('social_links.youtube')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Bio -->
                        <div class="mb-3 col-12">
                            <label for="bio" class="form-label">{{ __('Bio') }}</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" 
                                      id="bio" 
                                      name="bio" 
                                      rows="3">{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Profile Photo -->
                        <div class="mb-3 col-md-6">
                            <label for="profile_photo" class="form-label">{{ __('Profile Photo') }}</label>
                            @if($user->profile_photo_path || $profilePhoto)
                                <div class="mb-3">
                                    <img src="{{ $profilePhoto ? $profilePhoto : asset($defaultAvatar) }}" 
                                         alt="Current Profile Photo" 
                                         class="rounded-circle"     
                                         width="100">
                                </div>
                            @endif
                            <input class="form-control @error('profile_photo') is-invalid @enderror" 
                                   type="file" 
                                   id="profile_photo" 
                                   name="profile_photo" 
                                   accept="image/*" />
                            @error('profile_photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">{{ __('Save Changes') }}</button>
                        <a href="{{ route('dashboard.users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
