<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPermissionsSchema extends Command
{
    protected $signature = 'schema:fix-permissions';
    protected $description = 'Fix permissions table schema (Auto Increment ID)';

    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $conn) {
            $this->info("Checking connection: $conn");
            try {
                $hasTable = DB::connection($conn)->getSchemaBuilder()->hasTable('permissions');
                
                if ($hasTable) {
                    $this->info("Fixing 'permissions' table on $conn...");
                    // Try to add AUTO_INCREMENT and PRIMARY KEY
                    DB::connection($conn)->statement('ALTER TABLE permissions MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                    $this->info("Fixed.");
                } else {
                    $this->warn("Table 'permissions' not found on $conn.");
                }
            } catch (\Exception $e) {
                $this->error("Error on $conn: " . $e->getMessage());
                
                try {
                     // Retry without adding PRIMARY KEY (if it already exists)
                     DB::connection($conn)->statement('ALTER TABLE permissions MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                     $this->info("Fixed (retry without PK).");
                } catch (\Exception $e2) {
                     $this->error("Retry failed: " . $e2->getMessage());
                }
            }
        }
        return 0;
    }
}
