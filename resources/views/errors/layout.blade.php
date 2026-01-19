<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title', 'خطأ') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light dark; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Tajawal', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji';
            background: #0f172a; /* slate-900 */
            color: #e2e8f0; /* slate-200 */
        }
        .container {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 720px;
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
            border: 1px solid rgba(148,163,184,0.25);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(2,6,23,0.6);
            backdrop-filter: blur(6px);
        }
        .code {
            font-size: 56px;
            font-weight: 800;
            letter-spacing: 2px;
            line-height: 1;
            background: linear-gradient(90deg, #22d3ee, #a78bfa, #f472b6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin: 0 0 8px 0;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }
        .message {
            font-size: 16px;
            color: #cbd5e1; /* slate-300 */
            margin-bottom: 24px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        a.button, button.button {
            appearance: none;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: transform .08s ease, box-shadow .2s ease;
            background: #0ea5e9;
            color: white;
            box-shadow: 0 8px 24px rgba(14,165,233,0.35);
        }
        a.button.secondary {
            background: transparent;
            color: #e2e8f0;
            border: 1px solid rgba(148,163,184,0.35);
            box-shadow: none;
        }
        a.button:hover, button.button:hover {
            transform: translateY(-1px);
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8; /* slate-400 */
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="code">@yield('code', 'خطأ')</div>
        <div class="title">@yield('title', 'حدث خطأ')</div>
        <div class="message">@yield('message', 'نأسف للإزعاج. يرجى المحاولة لاحقًا.')</div>
        <div class="actions">
            <a class="button" href="{{ url('/') }}">العودة للصفحة الرئيسية</a>
            <a class="button secondary" href="javascript:history.back()">العودة للخلف</a>
            @hasSection('extra_action')
                @yield('extra_action')
            @endif
        </div>
        <div class="footer">
            {{-- يمكنك تخصيص تفاصيل الدعم هنا --}}
            <span>إذا استمر الخطأ، يرجى التواصل مع الدعم.</span>
        </div>
    </div>
</div>
</body>
</html>
