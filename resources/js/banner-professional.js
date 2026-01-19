/* ============================================
   Professional Banner System - JavaScript
   نظام البنر الاحترافي للتفاعلات
   ============================================ */

'use strict';

// Initialize banner interactions when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initBannerInteractions();
});

/**
 * Initialize all banner interaction features
 */
function initBannerInteractions() {
    const banner = document.querySelector('.professional-banner');

    if (!banner) return;

    // Add keyboard navigation
    banner.addEventListener('keypress', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            banner.click();
        }
    });

    // Hover effects removed to comply with AdSense policies
    // Excessive animations near ads can cause policy violations

    /* Feature cards hover effects - removed
    const featureCards = document.querySelectorAll('.banner-feature-card');
    */

    /* RTL badge interactions - removed
    const rtlBadge = document.querySelector('.banner-rtl-badge');
    */

    /* CTA button pulse effect - removed
    const ctaButton = document.querySelector('.banner-cta-button');
    */

    // Add analytics tracking if needed
    trackBannerView();
}

/**
 * Track banner view for analytics
 */
function trackBannerView() {
    // This can be connected to your analytics system
    console.log('Professional banner viewed');

    // Example: Send to Google Analytics if available
    if (typeof gtag !== 'undefined') {
        gtag('event', 'banner_view', {
            'event_category': 'engagement',
            'event_label': 'professional_banner'
        });
    }
}

/**
 * Track banner click
 */
function trackBannerClick() {
    console.log('Professional banner clicked');

    // Example: Send to Google Analytics if available
    if (typeof gtag !== 'undefined') {
        gtag('event', 'banner_click', {
            'event_category': 'engagement',
            'event_label': 'professional_banner_cta'
        });
    }
}

// Pulse animation removed to comply with AdSense policies
// Dynamic animations near ads can cause violations
/* Pulse animation code removed */
