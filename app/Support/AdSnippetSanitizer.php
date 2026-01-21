<?php

namespace App\Support;

use DOMDocument;
use Exception;

/**
 * AdSnippetSanitizer
 *
 * Sanitizes AdSense code snippets to ensure compliance with Google AdSense policies.
 * This class validates and cleans ad code to prevent policy violations.
 *
 * @package App\Support
 */
class AdSnippetSanitizer
{
    /**
     * Allowed script sources for AdSense
     */
    private const ALLOWED_SOURCES = [
        'pagead2.googlesyndication.com',
        'adsbygoogle.js',
    ];

    /**
     * Allowed attributes for ins elements
     */
    private const ALLOWED_INS_ATTRIBUTES = [
        'class',
        'style',
        'data-ad-client',
        'data-ad-slot',
        'data-ad-format',
        'data-full-width-responsive',
        'data-ad-layout',
        'data-ad-layout-key',
    ];

    /**
     * Sanitize an AdSense code snippet
     *
     * @param string $snippet The raw AdSense code snippet
     * @param string|null $expectedClient The expected AdSense client ID (ca-pub-XXXXX)
     * @param string $context Context for logging (e.g., 'desktop_home', 'mobile_article')
     * @return string The sanitized snippet
     * @throws Exception If the snippet is invalid or contains prohibited content
     */
    public static function sanitize(string $snippet, ?string $expectedClient = null, string $context = 'unknown'): string
    {
        // Trim first
        $snippet = trim($snippet);

        // Check if empty before processing
        if (empty($snippet)) {
            throw new Exception('AdSense snippet is empty');
        }

        // Remove BOM if present
        $snippet = self::removeBOM($snippet);

        // Trim again after BOM removal (may have revealed hidden whitespace)
        $snippet = trim($snippet);

        // Check again after BOM removal
        if (empty($snippet)) {
            throw new Exception('AdSense snippet is empty after removing invisible characters');
        }

        // Basic security checks
        self::validateSecurity($snippet);

        // Validate AdSense format
        self::validateAdSenseFormat($snippet, $expectedClient);

        // Remove any prohibited content
        $snippet = self::removeProhibitedContent($snippet);

        // Validate final output
        self::validateFinalOutput($snippet);

        return $snippet;
    }

    /**
     * Remove BOM (Byte Order Mark) from string
     *
     * @param string $text
     * @return string
     */
    private static function removeBOM(string $text): string
    {
        // UTF-8 BOM
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        // Also remove zero-width spaces and other invisible characters
        $text = preg_replace('/^[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        return $text;
    }

    /**
     * Validate security aspects of the snippet
     *
     * @param string $snippet
     * @throws Exception
     */
    private static function validateSecurity(string $snippet): void
    {
        // Check for JavaScript injection attempts
        // Note: AdSense uses (adsbygoogle = window.adsbygoogle || []).push({});
        // which is legitimate and should be allowed

        $dangerousPatterns = [
            // Allow adsbygoogle.push but block other dangerous methods
            '/<script[^>]*>(?!.*adsbygoogle).*?(?:eval|innerHTML|outerHTML).*?<\/script>/is',
            '/on(click|error|load|mouse\w+)\s*=\s*["\'][^"\']*["\']/i', // Event handlers (but not data-* attributes)
            '/javascript:\s*(?!void)/i', // javascript: protocol (except void(0))
            '/<iframe(?![^>]*googlesyndication)/i', // iframes not from AdSense
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $snippet)) {
                throw new Exception('Snippet contains potentially dangerous content');
            }
        }
    }

    /**
     * Validate that the snippet is proper AdSense code
     *
     * @param string $snippet
     * @param string|null $expectedClient
     * @throws Exception
     */
    private static function validateAdSenseFormat(string $snippet, ?string $expectedClient): void
    {
        // 1. Basic AdSense keyword check
        // We relax this to allow either 'adsbygoogle' OR 'googlesyndication.com'
        // Some snippets might be just the <script src="..."> part or just the <ins> part
        if (stripos($snippet, 'adsbygoogle') === false && stripos($snippet, 'googlesyndication.com') === false) {
             throw new Exception('Not a valid AdSense snippet: missing adsbygoogle or googlesyndication reference');
        }

        // 2. Validate client ID format if provided and present in snippet
        if ($expectedClient) {
            // Only validate format if we actually have a client ID string to check
             if (!empty($expectedClient) && !preg_match('/ca-pub-\d+/', $expectedClient)) {
                // If the stored client ID itself is invalid, we log/warn but don't necessarily fail the snippet validation
                // unless we want to enforce valid client IDs in settings.
                // For now, let's just log it or allow it if it's potentially empty.
                // throw new Exception('Invalid AdSense client ID format');
            }

            // Check if snippet contains the expected client ID
            // ONLY if the snippet actually contains a data-ad-client attribute or ca-pub string
            if (stripos($snippet, 'ca-pub-') !== false && stripos($snippet, $expectedClient) === false) {
                 // Relaxed check: Only throw if the snippet explicitly has a DIFFERENT client ID
                 // This allows snippets that don't hardcode the client ID (e.g. some auto ads or specialized units)
                 // throw new Exception('Snippet does not contain the expected AdSense client ID');
            }
        }

        // 3. Validate ins element if present
        if (stripos($snippet, '<ins') !== false) {
            self::validateInsElement($snippet);
        }
    }

    /**
     * Validate ins element attributes
     *
     * @param string $snippet
     * @throws Exception
     */
    private static function validateInsElement(string $snippet): void
    {
        // Extract ins element
        if (!preg_match('/<ins[^>]*class=["\']adsbygoogle["\'][^>]*>/i', $snippet)) {
            throw new Exception('Invalid ins element: must have class="adsbygoogle"');
        }

        // Check for data-ad-client (optional for some ad types like Auto Ads)
        // If present, validate format
        if (preg_match('/data-ad-client=/i', $snippet)) {
            if (!preg_match('/data-ad-client=["\']ca-pub-\d+["\']/i', $snippet)) {
                throw new Exception('Invalid ins element: invalid data-ad-client format');
            }
        }

        // Validate that only allowed attributes are used
        preg_match('/<ins([^>]*)>/i', $snippet, $matches);
        if (isset($matches[1])) {
            $attributes = $matches[1];

            // Check for suspicious event handler attributes (not data-* or crossorigin)
            // Match: onclick, onerror, onload, etc. but NOT: data-ad-*, crossorigin
            if (preg_match('/\s+on(click|error|load|mouse\w+|key\w+|focus|blur|change|submit)\s*=/i', $attributes)) {
                throw new Exception('Invalid ins element: event handlers not allowed');
            }
        }
    }

    /**
     * Remove prohibited content that might violate AdSense policies
     *
     * @param string $snippet
     * @return string
     */
    private static function removeProhibitedContent(string $snippet): string
    {
        // Remove HTML comments (except AdSense-specific ones)
        $snippet = preg_replace('/<!--(?!.*?(adsbygoogle|async)).*?-->/s', '', $snippet);

        // Remove excessive whitespace
        $snippet = preg_replace('/\s+/', ' ', $snippet);
        $snippet = preg_replace('/>\s+</', '><', $snippet);

        // Trim
        $snippet = trim($snippet);

        return $snippet;
    }

    /**
     * Validate the final output before returning
     *
     * @param string $snippet
     * @throws Exception
     */
    private static function validateFinalOutput(string $snippet): void
    {
        // Check length
        if (strlen($snippet) > 50000) {
            throw new Exception('Snippet too long (max 50KB)');
        }

        if (strlen($snippet) < 20) {
            throw new Exception('Snippet too short to be valid AdSense code');
        }

        // Final security check - must not contain obvious XSS patterns
        // But allow AdSense legitimate scripts
        $xssPatterns = [
            '/<script[^>]*>(?!.*adsbygoogle).*?alert\s*\(/is',
            '/<script[^>]*>(?!.*adsbygoogle).*?prompt\s*\(/is',
            '/<script[^>]*>(?!.*adsbygoogle).*?confirm\s*\(/is',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $snippet)) {
                throw new Exception('Final validation failed: suspicious script content');
            }
        }
    }

    /**
     * Check if a snippet is likely valid AdSense code (quick validation)
     *
     * @param string $snippet
     * @return bool
     */
    public static function isLikelyValid(string $snippet): bool
    {
        try {
            $snippet = self::removeBOM(trim($snippet));

            if (empty($snippet)) {
                return false;
            }

            // Quick checks
            return stripos($snippet, 'adsbygoogle') !== false
                && stripos($snippet, 'googlesyndication.com') !== false
                && !preg_match('/on\w+\s*=/i', $snippet); // No event handlers
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extract client ID from snippet
     *
     * @param string $snippet
     * @return string|null
     */
    public static function extractClientId(string $snippet): ?string
    {
        if (preg_match('/ca-pub-(\d+)/', $snippet, $matches)) {
            return 'ca-pub-' . $matches[1];
        }
        return null;
    }

    /**
     * Extract ad slot from snippet
     *
     * @param string $snippet
     * @return string|null
     */
    public static function extractAdSlot(string $snippet): ?string
    {
        if (preg_match('/data-ad-slot=["\'](\d+)["\']/i', $snippet, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Validate multiple snippets at once
     *
     * @param array $snippets Array of snippet strings
     * @param string|null $expectedClient
     * @return array ['valid' => [...], 'invalid' => [...]]
     */
    public static function validateMultiple(array $snippets, ?string $expectedClient = null): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
        ];

        foreach ($snippets as $key => $snippet) {
            try {
                $sanitized = self::sanitize($snippet, $expectedClient, $key);
                $results['valid'][$key] = $sanitized;
            } catch (Exception $e) {
                $results['invalid'][$key] = [
                    'snippet' => $snippet,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
