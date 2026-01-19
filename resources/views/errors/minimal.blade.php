<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? ('خطأ ' . ($code ?? '')) }}</title>
    <style>
        html, body { height: 100%; margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background: #0f172a; color: #e2e8f0; }
        .wrap { min-height: 100%; display: grid; place-items: center; padding: 24px; }
        .box { text-align: center; }
        .code { font-size: 40px; font-weight: 800; margin-bottom: 8px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .msg { color: #cbd5e1; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="box">
        <div class="code">{{ $code ?? 'خطأ' }}</div>
        <div class="title">{{ $title ?? 'حدث خطأ' }}</div>
        <div class="msg">{{ $message ?? 'نأسف للإزعاج. يرجى المحاولة لاحقًا.' }}</div>
    </div>
</div>
</body>
</html>
