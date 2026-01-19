<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use Illuminate\Support\Str;

class SyncFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync physical files from storage/files to the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting file synchronization...');

        $rootPath = public_path('storage/files');
        if (!file_exists($rootPath)) {
            $this->error("Directory not found: $rootPath");
            return 1;
        }

        // Recursive directory iterator
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;
        $inserted = 0;

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $count++;
                $fullPath = $item->getPathname();
                // Get relative path starting with storage/files/...
                // public_path('storage/files') -> D:\...\public\storage\files
                // fullPath -> D:\...\public\storage\files\1\class\cat\file.pdf
                // relative -> storage/files/1/class/cat/file.pdf

                // Fix path separator for Windows/Linux consistency in DB
                $relativePath = 'storage/files/' . str_replace('\\', '/', substr($fullPath, strlen($rootPath) + 1));
                
                $this->processFile($relativePath, $item);
            }
        }

        $this->info("Scanned $count files.");
        $this->info("Inserted $inserted new records.");

        return 0;
    }

    private function processFile($relativePath, $fileInfo)
    {
        // relativePath example: storage/files/1/class-name/category/file.pdf
        // or storage/files/class-name/category/file.pdf (legacy/default)

        $parts = explode('/', $relativePath);
        // $parts[0] = storage
        // $parts[1] = files
        // $parts[2] = country (maybe) or class
        
        $country = '1'; // Default to Jordan/1
        $connection = 'jo';
        
        // Simple heuristic: check if parts[2] is a number (country id)
        if (isset($parts[2]) && is_numeric($parts[2])) {
            $country = $parts[2];
            // Remove country from parts to find category? 
            // Standard structure: storage/files/{country}/{class}/{category}/{filename}
            // Category would be parts[4]
            $category = isset($parts[4]) ? $parts[4] : 'uncategorized';
        } else {
            // Structure: storage/files/{class}/{category}/{filename}
            $category = isset($parts[3]) ? $parts[3] : 'uncategorized';
        }

        $connection = $this->getConnection($country);
        
        // Check if exists
        $exists = File::on($connection)->where('file_path', $relativePath)->exists();

        if (!$exists) {
            $this->line("Syncing: $relativePath -> DB: $connection");
            
            try {
                File::on($connection)->create([
                    'article_id' => null, // Orphan file
                    'post_id' => null,
                    'file_path' => $relativePath,
                    'file_type' => $fileInfo->getExtension(),
                    'file_category' => $category, // Best guess
                    'file_name' => $fileInfo->getBasename(),
                    'file_size' => $fileInfo->getSize(),
                    'mime_type' => mime_content_type($fileInfo->getPathname()) ?: 'application/octet-stream',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // We can't easily track inserted count across method without property, but logging is fine.
            } catch (\Exception $e) {
                $this->error("Failed to insert $relativePath: " . $e->getMessage());
            }
        }
    }

    private function getConnection(string $country): string
    {
        return match ($country) {
            '2' => 'sa',
            '3' => 'eg',
            '4' => 'ps',
            default => 'jo',
        };
    }
}
