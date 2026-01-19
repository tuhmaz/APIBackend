<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPostKeywordSchema extends Command
{
    protected $signature = 'schema:fix-post-keyword';
    protected $description = 'Fix post_keyword table schema (Auto Increment ID)';

    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $conn) {
            $this->info("Checking connection: $conn");
            try {
                $hasTable = DB::connection($conn)->getSchemaBuilder()->hasTable('post_keyword');
                
                if ($hasTable) {
                    $this->info("Fixing 'post_keyword' table on $conn...");
                    // Try to add AUTO_INCREMENT and PRIMARY KEY
                    DB::connection($conn)->statement('ALTER TABLE post_keyword MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                    $this->info("Fixed.");
                } else {
                    $this->warn("Table 'post_keyword' not found on $conn.");
                }
            } catch (\Exception $e) {
                $this->error("Error on $conn: " . $e->getMessage());
                
                try {
                     // Retry without adding PRIMARY KEY (if it already exists but not auto-increment)
                     DB::connection($conn)->statement('ALTER TABLE post_keyword MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                     $this->info("Fixed (retry without PK).");
                } catch (\Exception $e2) {
                     $this->error("Retry failed: " . $e2->getMessage());
                }
            }
        }
        return 0;
    }
}
