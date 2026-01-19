<!-- Professional Banner Section -->
<article class="banner-wrapper" id="bannerWrapper">
    <div class="professional-banner-wrapper">
        <div class="professional-banner" role="button" tabindex="0" aria-label="انقر للانتقال إلى موقع خدمتك" onclick="window.open('https://khadmatak.com/', '_blank')">
            <!-- Background Decorations -->
            <div class="banner-decoration banner-decoration-1" aria-hidden="true"></div>
            <div class="banner-decoration banner-decoration-2" aria-hidden="true"></div>
            <div class="banner-decoration banner-decoration-3" aria-hidden="true"></div>

            <!-- Main Container -->
            <div class="banner-container">
                <!-- Left Section: Logo -->
                <div class="banner-logo-section">
                    <div class="banner-logo-container" role="img" aria-label="شعار خدمتك">
                        <img src="{{ asset('assets/khadmatak.png') }}" alt="شعار خدمتك" class="banner-logo">
                    </div>
                </div>

                <!-- Center Section: Content -->
                <div class="banner-content-section">
                    <h1 class="banner-title">خدمتك</h1>
                    <p class="banner-subtitle">نظام لإدارة سجلات الطلبة والمعلمين</p>
                    <p class="banner-description">استخلاص تلقائي • سجلات احترافية • طباعة فورية</p>
                </div>

                <!-- Right Section: Features & CTA -->
                <div class="banner-features-section">
                    <!-- Features Grid -->
                    <div class="banner-features-grid">
                        <div class="banner-feature-item">
                            <i class="panar-icon ti tabler-cloud"></i>
                            <span>متوافق أجيال</span>
                        </div>
                        <div class="banner-feature-item">
                            <i class="panar-icon ti tabler-book"></i>
                            <span>دفاتر حضور</span>
                        </div>
                        <div class="banner-feature-item">
                            <i class="panar-icon ti tabler-printer"></i>
                            <span>طباعة فورية</span>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <button class="banner-cta-button" onclick="event.stopPropagation(); window.open('https://khadmatak.com/', '_blank')" aria-label="ابدأ استخدام النظام الآن">
                        <span>ابدأ الآن</span>
                        <i class="panar-icon ti tabler-arrow-left"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</article>
