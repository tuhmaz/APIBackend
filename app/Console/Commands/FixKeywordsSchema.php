<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixKeywordsSchema extends Command
{
    protected $signature = 'schema:fix-keywords';
    protected $description = 'Fix keywords table schema (Auto Increment ID)';

    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $conn) {
            $this->info("Checking connection: $conn");
            try {
                $hasTable = DB::connection($conn)->getSchemaBuilder()->hasTable('keywords');
                
                if ($hasTable) {
                    $this->info("Fixing 'keywords' table on $conn...");
                    
                    // Check if Primary Key exists
                    $hasPrimaryKey = false;
                    try {
                        $keys = DB::connection($conn)->select("SHOW KEYS FROM keywords WHERE Key_name = 'PRIMARY'");
                        if (count($keys) > 0) {
                            $hasPrimaryKey = true;
                        }
                    } catch (\Exception $e) {
                        // Ignore
                    }

                    if ($hasPrimaryKey) {
                        // If PK exists, just add AUTO_INCREMENT
                        DB::connection($conn)->statement('ALTER TABLE keywords MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                        $this->info("Fixed (Added AUTO_INCREMENT to existing PK).");
                    } else {
                        // If no PK, add both
                        DB::connection($conn)->statement('ALTER TABLE keywords MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                        $this->info("Fixed (Added AUTO_INCREMENT and PRIMARY KEY).");
                    }
                } else {
                    $this->warn("Table 'keywords' not found on $conn.");
                }
            } catch (\Exception $e) {
                $this->error("Error on $conn: " . $e->getMessage());
                
                // Fallback attempt
                try {
                     DB::connection($conn)->statement('ALTER TABLE keywords MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                     $this->info("Fixed (Fallback: Added AUTO_INCREMENT).");
                } catch (\Exception $e2) {
                     $this->error("Fallback failed: " . $e2->getMessage());
                }
            }
        }
        return 0;
    }
}
