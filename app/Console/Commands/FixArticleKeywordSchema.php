<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixArticleKeywordSchema extends Command
{
    protected $signature = 'schema:fix-article-keyword';
    protected $description = 'Fix article_keyword table schema (Auto Increment ID)';

    public function handle()
    {
        $connections = ['jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $conn) {
            $this->info("Checking connection: $conn");
            try {
                $hasTable = DB::connection($conn)->getSchemaBuilder()->hasTable('article_keyword');
                
                if ($hasTable) {
                    $this->info("Fixing 'article_keyword' table on $conn...");
                    // Try to add AUTO_INCREMENT and PRIMARY KEY
                    // Note: If PK already exists, this might fail, so we'll catch and retry
                    DB::connection($conn)->statement('ALTER TABLE article_keyword MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
                    $this->info("Fixed.");
                } else {
                    $this->warn("Table 'article_keyword' not found on $conn.");
                }
            } catch (\Exception $e) {
                $this->error("Error on $conn: " . $e->getMessage());
                
                try {
                     // Retry without adding PRIMARY KEY (if it already exists)
                     DB::connection($conn)->statement('ALTER TABLE article_keyword MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
                     $this->info("Fixed (retry without PK).");
                } catch (\Exception $e2) {
                     $this->error("Retry failed: " . $e2->getMessage());
                }
            }
        }
        return 0;
    }
}
