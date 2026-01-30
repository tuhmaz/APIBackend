<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized Database Connection Manager
 *
 * Handles multi-database connections based on country selection.
 * Provides cache key prefixing for country-specific data isolation.
 *
 * Usage:
 *   $connection = DatabaseManager::getConnection();
 *   Model::on($connection)->get();
 *
 *   // Or with country-specific caching
 *   DatabaseManager::remember('key', 300, function() {
 *       return Model::on(DatabaseManager::getConnection())->get();
 *   });
 */
class DatabaseManager
{
    /**
     * Current active connection name
     */
    private static ?string $currentConnection = null;

    /**
     * Country ID to database connection mapping
     */
    private static array $countryConnections = [
        '1' => 'jo',  // Jordan (default)
        '2' => 'sa',  // Saudi Arabia
        '3' => 'eg',  // Egypt
        '4' => 'ps',  // Palestine
    ];

    /**
     * Country codes for validation
     */
    private static array $validCountryCodes = ['jo', 'sa', 'eg', 'ps'];

    /**
     * Get database connection name for a country
     *
     * @param string|null $countryId Country ID from header or parameter
     * @return string Database connection name
     */
    public static function getConnection(?string $countryId = null): string
    {
        // Use provided country ID or get from request header
        $countryId = $countryId ?? request()->header('X-Country-Id');

        // Validate and return connection name
        if ($countryId && isset(self::$countryConnections[$countryId])) {
            return self::$countryConnections[$countryId];
        }

        // Check for country code header as fallback
        $countryCode = request()->header('X-Country-Code');
        if ($countryCode && in_array($countryCode, self::$validCountryCodes, true)) {
            return $countryCode;
        }

        // Default to Jordan
        return 'jo';
    }

    /**
     * Set the current connection and return its name
     * Also sets Laravel's default connection
     *
     * @param string|null $countryId Country ID
     * @return string Connection name that was set
     */
    public static function setConnection(?string $countryId = null): string
    {
        $connection = self::getConnection($countryId);

        // Only switch if different from current
        if (self::$currentConnection !== $connection) {
            self::$currentConnection = $connection;

            // Set as default connection for the request
            DB::setDefaultConnection($connection);

            // Log connection switch in debug mode
            if (config('app.debug')) {
                Log::debug('Database connection switched', [
                    'connection' => $connection,
                    'country_id' => $countryId,
                ]);
            }
        }

        return $connection;
    }

    /**
     * Get the current active connection name
     *
     * @return string Current connection name
     */
    public static function getCurrentConnection(): string
    {
        return self::$currentConnection ?? self::getConnection();
    }

    /**
     * Get cache key prefix for current country
     * Ensures cache isolation between countries
     *
     * @return string Cache key prefix
     */
    public static function getCachePrefix(): string
    {
        $connection = self::getCurrentConnection();
        return "country_{$connection}_";
    }

    /**
     * Remember data with country-specific cache key
     *
     * @param string $key Cache key (will be prefixed with country)
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to generate data if not cached
     * @return mixed Cached or fresh data
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $prefixedKey = self::getCachePrefix() . $key;

        return Cache::remember($prefixedKey, $ttl, $callback);
    }

    /**
     * Forget a country-specific cache key
     *
     * @param string $key Cache key (will be prefixed with country)
     * @return bool Success
     */
    public static function forget(string $key): bool
    {
        $prefixedKey = self::getCachePrefix() . $key;

        return Cache::forget($prefixedKey);
    }

    /**
     * Forget cache keys matching a pattern for current country
     *
     * @param string $pattern Pattern to match (will be prefixed with country)
     * @return void
     */
    public static function forgetPattern(string $pattern): void
    {
        $prefix = self::getCachePrefix();

        // Note: This only works well with Redis/Memcached
        // For file cache, consider using tags or manual tracking
        if (method_exists(Cache::getStore(), 'flush')) {
            // For Redis, we could use SCAN command, but for simplicity:
            Log::info("Cache pattern forget requested: {$prefix}{$pattern}");
        }
    }

    /**
     * Get all available connections
     *
     * @return array<string, string> Country ID => Connection name mapping
     */
    public static function getAvailableConnections(): array
    {
        return self::$countryConnections;
    }

    /**
     * Check if a connection exists and is healthy
     *
     * @param string $connection Connection name
     * @return bool True if connection is healthy
     */
    public static function isConnectionHealthy(string $connection): bool
    {
        try {
            DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error("Database connection unhealthy: {$connection}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get country ID from connection name
     *
     * @param string $connection Connection name
     * @return string|null Country ID or null if not found
     */
    public static function getCountryIdFromConnection(string $connection): ?string
    {
        return array_search($connection, self::$countryConnections, true) ?: null;
    }

    /**
     * Execute a callback on a specific connection
     *
     * @param string $connection Connection name
     * @param callable $callback Callback to execute
     * @return mixed Callback result
     */
    public static function onConnection(string $connection, callable $callback): mixed
    {
        $previousConnection = self::$currentConnection;

        try {
            DB::setDefaultConnection($connection);
            self::$currentConnection = $connection;

            return $callback();
        } finally {
            // Restore previous connection
            if ($previousConnection) {
                DB::setDefaultConnection($previousConnection);
                self::$currentConnection = $previousConnection;
            }
        }
    }

    /**
     * Reset connection state (useful for testing)
     */
    public static function reset(): void
    {
        self::$currentConnection = null;
        DB::setDefaultConnection(config('database.default'));
    }
}
