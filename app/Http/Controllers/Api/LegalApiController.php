<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Resources\BaseResource;

class LegalApiController extends Controller
{
    /**
     * GET /api/legal/privacy-policy
     */
    public function privacyPolicy()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return new BaseResource([
            'page' => 'privacy_policy',
            'settings' => $settings,
        ]);
    }

    /**
     * GET /api/legal/terms-of-service
     */
    public function termsOfService()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return new BaseResource([
            'page' => 'terms_of_service',
            'settings' => $settings,
        ]);
    }

    /**
     * GET /api/legal/cookie-policy
     */
    public function cookiePolicy()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return new BaseResource([
            'page' => 'cookie_policy',
            'settings' => $settings,
        ]);
    }

    /**
     * GET /api/legal/disclaimer
     */
    public function disclaimer()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return new BaseResource([
            'page' => 'disclaimer',
            'settings' => $settings,
        ]);
    }
}
