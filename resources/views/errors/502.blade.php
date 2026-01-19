@extends('errors.layout')

@section('page_title', 'بوابة غير صالحة')
@section('code', '502')
@section('title', 'خطأ من الخادم الوسيط')
@section('message')
    تلقّى الخادم استجابة غير صالحة من خادم آخر. يرجى المحاولة لاحقًا.
@endsection
