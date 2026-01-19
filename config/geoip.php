<?php

return [
    // Enable/disable external HTTP GeoIP lookups
    'http_enabled' => env('GEOIP_HTTP_ENABLED', true),

    // Timeout in seconds for HTTP client
    'http_timeout' => env('GEOIP_HTTP_TIMEOUT', 3),

    // Cache TTLs (in seconds)
    'cache_ttl_success' => env('GEOIP_CACHE_TTL_SUCCESS', 86400), // 1 day
    'cache_ttl_failure' => env('GEOIP_CACHE_TTL_FAILURE', 21600), // 6 hours

    // Provider chain (in order). Supported: ipwhois, ipapi, ipapicom
    'providers' => explode(',', env('GEOIP_PROVIDERS', 'ipwhois,ipapi,ipapicom')),

    // Circuit breaker: number of consecutive failures before temporarily disabling a provider
    'provider_failure_threshold' => env('GEOIP_PROVIDER_FAILURE_THRESHOLD', 5),

    // Log on total failure (after all providers fail). Per-IP warning throttled internally.
    'log_on_failure' => env('GEOIP_LOG_ON_FAILURE', true),
];
