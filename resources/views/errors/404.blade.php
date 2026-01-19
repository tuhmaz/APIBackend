@extends('errors.layout')

@section('page_title', 'الصفحة غير موجودة')
@section('code', '404')
@section('title', 'الصفحة غير موجودة')
@section('message')
    عذراً، الصفحة التي تحاول الوصول إليها غير موجودة أو قد تم نقلها.
    <br>
    <span id="countdown-status">
        سيتم تحويلك تلقائياً إلى الصفحة الرئيسية خلال
        <strong><span id="countdown">5</span></strong>
        ثوانٍ.
    </span>
@endsection

@section('extra_action')
    <a class="button" href="{{ url('/') }}">الذهاب الآن</a>
    <a class="button secondary" href="#" id="cancel-auto">إيقاف التحويل التلقائي</a>
    <script>
        (function () {
            var HOME_URL = "{{ url('/') }}";
            var seconds = 5;
            var cancelled = false;
            var countdownEl = document.getElementById('countdown');
            var statusEl = document.getElementById('countdown-status');

            function tick() {
                if (cancelled) return;
                seconds -= 1;
                if (countdownEl) countdownEl.textContent = Math.max(seconds, 0);
                if (seconds <= 0) {
                    window.location.href = HOME_URL;
                } else {
                    setTimeout(tick, 1000);
                }
            }

            setTimeout(tick, 1000);

            var cancelBtn = document.getElementById('cancel-auto');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    cancelled = true;
                    if (statusEl) statusEl.textContent = 'تم إيقاف التحويل التلقائي.';
                });
            }
        })();
    </script>
@endsection
