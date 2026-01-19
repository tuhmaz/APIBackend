<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ApiDatabaseTrait
{
    /**
     * Map a country identifier to a DB connection name.
     *
     * @param string $country
     * @return string connection name
     */
    protected function mapCountryToConnection(string $country): string
    {
        $c = strtolower(trim($country));
        return match ($c) {
            'saudi', 'sa', '2' => 'sa',
            'egypt', 'eg', '3' => 'eg',
            'palestine', 'ps', '4' => 'ps',
            'jordan', 'jo', '1' => 'jo',
            default => throw new NotFoundHttpException(__('Invalid country selected')),
        };
    }

    /**
     * Switch the default DB connection for the current request context.
     * Also stores the chosen database in the session for consistency with frontend.
     *
     * @param string $country
     * @return string The connection name used
     */
    protected function switchDatabase(string $country): string
    {
        $connection = $this->mapCountryToConnection($country);
        DB::setDefaultConnection($connection);
        // Keep session aligned for any downstream consumers
        if (function_exists('session')) {
            session(['database' => $connection]);
        }
        return $connection;
    }
}
