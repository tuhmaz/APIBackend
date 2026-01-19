<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\BaseResource;

class LocalizationApiController extends Controller
{
    /**
     * Change the application language (API version)
     *
     * POST /api/lang/change
     * Body: { "locale": "ar" }
     */
    public function changeLanguage(Request $request)
    {
        $locale = $request->input('locale');

        if (!in_array($locale, ['en', 'ar'])) {
            return (new BaseResource(['message' => 'Invalid locale. Supported: en, ar.']))
                ->response($request)
                ->setStatusCode(400);
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return new BaseResource([
            'message' => 'Language changed successfully.',
            'locale' => $locale,
        ]);
    }

    /**
     * GET /api/lang/current
     * Returns the active language stored in session
     */
    public function currentLanguage()
    {
        $locale = Session::get('locale', config('app.locale'));

        return new BaseResource([
            'locale' => $locale,
        ]);
    }
}
