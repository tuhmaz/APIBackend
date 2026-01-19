@extends('errors.layout')

@section('page_title', 'حدث خطأ')
@section('code')
    {{ $code ?? 'خطأ' }}
@endsection
@section('title')
    {{ $title ?? 'عُذرًا، حدث خطأ' }}
@endsection
@section('message')
    {{ $message ?? 'نأسف للإزعاج. يرجى المحاولة لاحقًا.' }}
@endsection
