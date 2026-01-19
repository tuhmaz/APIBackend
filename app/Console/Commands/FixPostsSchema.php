<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixPostsSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:fix-schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix posts table schema (add AUTO_INCREMENT to id)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $connection) {
            $this->info("Checking connection: $connection");

            try {
                // Check if table exists
                if (!Schema::connection($connection)->hasTable('posts')) {
                    $this->warn("Table 'posts' does not exist in $connection");
                    continue;
                }

                // Make id AUTO_INCREMENT
                // Using raw SQL because Schema builder modifications for auto increment can be tricky across versions/drivers
                // assuming MySQL/MariaDB
                try {
                    DB::connection($connection)->statement('ALTER TABLE posts MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                } catch (\Exception $e) {
                    // SQLSTATE[42000]: Syntax error or access violation: 1075 Incorrect table definition; there can be only one auto column and it must be defined as a key
                    if (str_contains($e->getMessage(), '1075')) {
                        $this->warn("Primary Key missing in $connection, adding it...");
                        DB::connection($connection)->statement('ALTER TABLE posts ADD PRIMARY KEY (id)');
                        DB::connection($connection)->statement('ALTER TABLE posts MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                    } else {
                        throw $e;
                    }
                }
                
                $this->info("Successfully updated 'posts' table schema in $connection");

            } catch (\Exception $e) {
                $this->error("Error in $connection: " . $e->getMessage());
            }
        }
    }
}
