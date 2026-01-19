@php
    $cookieConsentConfig = config('cookie-consent');
    $cookieName = $cookieConsentConfig['cookie_name'] ?? 'laravel_cookie_consent';
    $alreadyConsentedWithCookies = isset($_COOKIE[$cookieName]);
@endphp

@if(!$alreadyConsentedWithCookies && ($cookieConsentConfig['enabled'] ?? true))
    <div class="cookie-consent js-cookie-consent">
        <p class="cookie-consent__message" style="color: white;">
            {{ __('cookie-consent.message') }}
        </p>

        <button class="cookie-consent__agree js-cookie-consent-agree">
            {{ __('agree') }}
        </button>
    </div>

    <script>
        window.laravelCookieConsent = (function () {
            const COOKIE_VALUE = 1;
            const COOKIE_NAME = '{{ $cookieName }}';
            const COOKIE_DOMAIN = '{{ config('session.domain') ?? request()->getHost() }}';

            function emitConsent(initial = false) {
                window.dispatchEvent(new CustomEvent('cookie-consent:accepted', {
                    detail: { initial }
                }));
            }

            function consentWithCookies() {
                setCookie(COOKIE_NAME, COOKIE_VALUE, {{ $cookieConsentConfig['cookie_lifetime'] ?? 365 }});
                hideCookieDialog();
                emitConsent(false);
            }

            function cookieExists(name) {
                return (document.cookie.split('; ').indexOf(name + '=' + COOKIE_VALUE) !== -1);
            }

            function hideCookieDialog() {
                const dialogs = document.getElementsByClassName('js-cookie-consent');
                for (let i = 0; i < dialogs.length; ++i) {
                    dialogs[i].style.display = 'none';
                }
            }

            function setCookie(name, value, expirationInDays) {
                const date = new Date();
                date.setTime(date.getTime() + (expirationInDays * 24 * 60 * 60 * 1000));
                document.cookie = name + '=' + value
                    + ';expires=' + date.toUTCString()
                    + ';domain=' + COOKIE_DOMAIN
                    + ';path=/'
                    + '{{ config('session.secure') ? ';secure' : '' }}'
                    + '{{ config('session.same_site') ? ';samesite='.config('session.same_site') : '' }}';
            }

            const hasConsent = cookieExists(COOKIE_NAME);

            if (hasConsent) {
                hideCookieDialog();
                emitConsent(true);
            }

            const buttons = document.getElementsByClassName('js-cookie-consent-agree');
            for (let i = 0; i < buttons.length; ++i) {
                buttons[i].addEventListener('click', consentWithCookies);
            }

            return {
                consentWithCookies: consentWithCookies,
                hideCookieDialog: hideCookieDialog,
                alreadyConsented: hasConsent
            };
        })();
    </script>
@endif
