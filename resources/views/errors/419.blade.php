@extends('errors.layout')

@section('page_title', 'انتهت الجلسة')
@section('code', '419')
@section('title', 'انتهت صلاحية الصفحة')
@section('message')
    انتهت صلاحية الطلب بسبب انتهاء الجلسة أو مشكلة في التحقق الأمني (CSRF). يرجى تحديث الصفحة والمحاولة مرة أخرى.
@endsection
@section('extra_action')
    <a class="button" href="{{ url()->current() }}">تحديث الصفحة</a>
@endsection
