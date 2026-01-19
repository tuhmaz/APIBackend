<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('migrations:fix-schema', function () {
    /** @var ClosureCommand $this */
    $connections = ['mysql', 'jo', 'sa', 'eg', 'ps'];

    foreach ($connections as $connection) {
        if (!config("database.connections.$connection")) {
            continue;
        }

        if (!Schema::connection($connection)->hasTable('migrations')) {
            $this->warn("Table 'migrations' does not exist in $connection");
            continue;
        }

        try {
            DB::connection($connection)->statement(
                'ALTER TABLE migrations MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
            $this->info("Updated 'migrations' schema in $connection");
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '1075')) {
                $this->warn("Primary key missing in $connection, adding it...");
                DB::connection($connection)->statement('ALTER TABLE migrations ADD PRIMARY KEY (id)');
                DB::connection($connection)->statement(
                    'ALTER TABLE migrations MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
                );
                $this->info("Updated 'migrations' schema in $connection");
            } else {
                $this->error("Error in $connection: " . $e->getMessage());
            }
        }
    }
})->purpose('Fix migrations table schema (add AUTO_INCREMENT to id)');

// Notifications pruning schedule
// Daily prune: remove read notifications older than 3 days
Schedule::command('notifications:prune --days=3')->dailyAt('02:30')->withoutOverlapping();

// Weekly deep prune: remove all notifications (including unread) older than 60 days
Schedule::command('notifications:prune --days=60 --all')->weeklyOn(1, '03:00')->withoutOverlapping();

// Activity log cleanup: keep only last 7 days
Schedule::command('activitylog:clean')->weeklyOn(1, '03:15')->withoutOverlapping();
