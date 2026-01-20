<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune {--days=3 : Delete notifications older than this many days} {--all : Also delete UNREAD notifications}';

    protected $description = 'Prune old notifications to keep the notifications table small.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('Days must be >= 1');
            return self::FAILURE;
        }
        $threshold = now()->subDays($days);
        $deleteUnread = (bool) $this->option('all');

        $this->info(sprintf('Pruning notifications older than %s (%d days). %s',
            $threshold->toDateTimeString(), $days, $deleteUnread ? 'Including UNREAD.' : 'Only READ.'));

        $total = 0;
        do {
            $query = DB::connection('jo')->table('notifications')->where('created_at', '<', $threshold);
            if (!$deleteUnread) {
                $query->whereNotNull('read_at');
            }
            // Delete in batches to avoid long locks
            $deleted = $query->limit(2000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->info("Deleted {$total} notifications.");
        return self::SUCCESS;
    }
}
