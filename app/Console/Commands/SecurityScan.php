<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\DB;

class SecurityScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a quick application security scan and print a short summary.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting security scan...');

        try {
            // Basic stats
            $criticalLast24h = SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $unresolvedIssues = SecurityLog::where('is_resolved', false)->count();

            $topRoutes = SecurityLog::select('route', DB::raw('count(*) as count'))
                ->whereNotNull('route')
                ->groupBy('route')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            $this->line('Critical events in last 24h: ' . $criticalLast24h);
            $this->line('Unresolved issues: ' . $unresolvedIssues);

            if ($topRoutes->isNotEmpty()) {
                $this->line('Most targeted routes:');
                foreach ($topRoutes as $r) {
                    $this->line(sprintf('- %s (%d)', $r->route ?? 'N/A', $r->count));
                }
            }

            $this->info('Security scan completed successfully.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Scan failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
