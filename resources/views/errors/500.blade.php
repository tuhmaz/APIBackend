@extends('errors.layout')

@section('page_title', 'خطأ في الخادم')
@section('code', '500')
@section('title', 'حدث خطأ غير متوقع')
@section('message')
    حدث خطأ غير متوقع في الخادم. فريقنا يعمل على حل المشكلة.
@endsection
