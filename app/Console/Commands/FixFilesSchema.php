<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFilesSchema extends Command
{
    protected $signature = 'files:fix-schema';
    protected $description = 'Fix files table schema (Auto Increment ID)';

    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $conn) {
            $this->info("Checking connection: $conn");
            try {
                // Check if table exists
                $hasTable = DB::connection($conn)->getSchemaBuilder()->hasTable('files');
                
                if ($hasTable) {
                    $this->info("Fixing 'files' table on $conn...");
                    // Force Auto Increment on ID
                    DB::connection($conn)->statement('ALTER TABLE files MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                    $this->info("Fixed.");
                } else {
                    $this->warn("Table 'files' not found on $conn.");
                }
            } catch (\Exception $e) {
                // Usually fails if it's already PK or AI, or if there are foreign key constraints issues?
                // If it fails with "Multiple primary key defined", we try just modifying the column.
                $this->error("Error on $conn: " . $e->getMessage());
                
                try {
                     DB::connection($conn)->statement('ALTER TABLE files MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                     $this->info("Fixed (retry without PK).");
                } catch (\Exception $e2) {
                     $this->error("Retry failed: " . $e2->getMessage());
                }
            }
        }
        return 0;
    }
}
