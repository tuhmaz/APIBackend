<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VisitorSession;

class PruneVisitorSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visitor-sessions:prune {--minutes=30 : Delete sessions inactive for this many minutes} {--only-bots : Only prune bot sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune inactive visitor sessions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $query = VisitorSession::query()->where('last_activity', '<', $cutoff);
        if ($this->option('only-bots')) {
            $query->where('is_bot', true);
        }

        $deleted = $query->delete();

        $this->info("Pruned {$deleted} visitor sessions older than {$minutes} minutes.");

        return self::SUCCESS;
    }
}
