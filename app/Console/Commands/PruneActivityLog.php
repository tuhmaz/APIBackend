<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneActivityLog extends Command
{
    /**
     * Available database connections
     */
    protected array $connections = ['jo', 'sa', 'eg', 'ps'];

    protected $signature = 'activitylog:prune-all
                            {--days=7 : Delete activity logs older than this many days}
                            {--connection= : Specific connection to prune (default: all)}';

    protected $description = 'Prune old activity logs from all databases.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('Days must be >= 1');
            return self::FAILURE;
        }

        $threshold = now()->subDays($days);
        $specificConnection = $this->option('connection');

        // Determine which connections to process
        $connectionsToProcess = $specificConnection
            ? [$specificConnection]
            : $this->connections;

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║            🧹 Activity Log Cleanup Started                    ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');
        $this->info(sprintf('📅 Threshold: %s (%d days ago)', $threshold->toDateTimeString(), $days));
        $this->info(sprintf('🗄️  Databases: %s', implode(', ', $connectionsToProcess)));
        $this->info('');

        $grandTotal = 0;
        $results = [];

        foreach ($connectionsToProcess as $connection) {
            $this->line("Processing database: <fg=cyan>{$connection}</>");

            try {
                $deleted = $this->pruneConnection($connection, $threshold);
                $results[$connection] = ['success' => true, 'deleted' => $deleted];
                $grandTotal += $deleted;

                $this->info("  ✓ Deleted {$deleted} activity logs from {$connection}");
            } catch (\Exception $e) {
                $results[$connection] = ['success' => false, 'error' => $e->getMessage()];
                $this->error("  ✗ Error on {$connection}: {$e->getMessage()}");

                Log::error('Activity log pruning failed', [
                    'connection' => $connection,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('');
        $this->info('════════════════════════════════════════════════════════════════');
        $this->info(sprintf('🎉 Total deleted: <fg=green>%d</> activity logs', $grandTotal));
        $this->info('════════════════════════════════════════════════════════════════');
        $this->info('');

        // Log the cleanup
        Log::info('Activity logs pruned', [
            'days' => $days,
            'threshold' => $threshold->toDateTimeString(),
            'total_deleted' => $grandTotal,
            'results' => $results,
        ]);

        return self::SUCCESS;
    }

    /**
     * Prune activity logs from a specific connection
     */
    protected function pruneConnection(string $connection, $threshold): int
    {
        $tableName = config('activitylog.table_name', 'activity_log');
        $total = 0;
        $batchSize = 2000;

        do {
            $deleted = DB::connection($connection)
                ->table($tableName)
                ->where('created_at', '<', $threshold)
                ->limit($batchSize)
                ->delete();

            $total += $deleted;

            // Small delay to prevent database overload
            if ($deleted > 0) {
                usleep(50000); // 50ms
            }
        } while ($deleted > 0);

        return $total;
    }

    /**
     * Static method for programmatic access (API usage)
     */
    public static function pruneOldActivityLogs(int $days = 7, ?string $connection = null): array
    {
        $command = new self();
        $threshold = now()->subDays($days);
        $connections = $connection ? [$connection] : $command->connections;

        $results = [
            'success' => true,
            'days' => $days,
            'threshold' => $threshold->toDateTimeString(),
            'total_deleted' => 0,
            'details' => [],
        ];

        foreach ($connections as $conn) {
            try {
                $deleted = $command->pruneConnection($conn, $threshold);
                $results['details'][$conn] = ['success' => true, 'deleted' => $deleted];
                $results['total_deleted'] += $deleted;
            } catch (\Exception $e) {
                $results['details'][$conn] = ['success' => false, 'error' => $e->getMessage()];
                $results['success'] = false;
            }
        }

        Log::info('Activity logs pruned via API', $results);

        return $results;
    }
}
