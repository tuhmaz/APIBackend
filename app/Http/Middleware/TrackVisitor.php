<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\VisitorTracking;
use Illuminate\Support\Facades\Auth;
use App\Services\VisitorService;

class TrackVisitor
{
    protected $visitorService;
    
    public function __construct(VisitorService $visitorService)
    {
        $this->visitorService = $visitorService;
    }
    
    public function handle(Request $request, Closure $next)
    {
        $ipAddress = $request->ip();

        // Get location information using our VisitorService
        $geoData = $this->visitorService->getGeoDataFromIP($ipAddress);

        // Parse user agent for browser and OS
        $uaData = $this->visitorService->analyzeUserAgent($request->userAgent());
        $browser = $uaData['client']['name'] ?? 'Unknown';
        $os = $uaData['os']['name'] ?? 'Unknown';

        // Update or create visitor tracking record
        $visitor = VisitorTracking::updateOrCreate(
            ['ip_address' => $ipAddress],
            [
                'user_agent' => $request->userAgent(),
                'country' => $geoData['country'] ?? null,
                'city' => $geoData['city'] ?? null,
                'browser' => $browser,
                'os' => $os,
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
                'last_activity' => now()
            ]
        );

        // Track page visit
        $visitor->pageVisits()->create([
            'page_url' => $request->fullUrl()
        ]);

        return $next($request);
    }
}
