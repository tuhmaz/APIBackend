<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class LegalController extends Controller
{
    public function privacyPolicy()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('legal.privacy-policy', compact('settings'));
    }

    public function termsOfService()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('legal.terms-of-service', compact('settings'));
    }

    public function cookiePolicy()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('legal.cookie-policy', compact('settings'));
    }

    public function disclaimer()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('legal.disclaimer', compact('settings'));
    }
}
