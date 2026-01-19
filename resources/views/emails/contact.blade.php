<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>{{ $data['subject'] ?? 'رسالة تواصل جديدة' }}</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; background:#f7f7f7; margin:0; padding:20px; }
    .container { max-width:600px; margin:0 auto; background:#fff; border-radius:8px; padding:20px; }
    .row { margin-bottom:10px; }
    .label { color:#6b7280; min-width:100px; display:inline-block; }
    .val { font-weight:600; }
    .hr { border-top:1px solid #eee; margin:16px 0; }
    .msg { white-space:pre-wrap; line-height:1.7; }
  </style>
</head>
<body>
  <div class="container">
    <h3 style="margin-top:0;">{{ __('تفاصيل رسالة التواصل') }}</h3>
    <div class="row"><span class="label">{{ __('الاسم') }}:</span> <span class="val">{{ $data['name'] ?? '' }}</span></div>
    <div class="row"><span class="label">{{ __('البريد') }}:</span> <span class="val">{{ $data['email'] ?? '' }}</span></div>
    @if(!empty($data['phone']))
    <div class="row"><span class="label">{{ __('الهاتف') }}:</span> <span class="val">{{ $data['phone'] }}</span></div>
    @endif
    <div class="row"><span class="label">{{ __('الموضوع') }}:</span> <span class="val">{{ $data['subject'] ?? '' }}</span></div>
    <div class="hr"></div>
    <div class="msg">{!! nl2br(e($data['message'] ?? '')) !!}</div>
  </div>
</body>
</html>
