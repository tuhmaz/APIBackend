<?php

namespace App\Jobs;

use App\Services\SitemapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSitemapJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $database;
    protected $type;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public $uniqueFor = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(string $database, string $type = 'articles')
    {
        $this->database = $database;
        $this->type = $type;

        // Run on low priority queue
        $this->onQueue('sitemap');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->database . '_' . $this->type;
    }

    /**
     * Execute the job.
     */
    public function handle(SitemapService $sitemapService): void
    {
        try {
            switch ($this->type) {
                case 'articles':
                    $sitemapService->generateArticlesSitemap($this->database);
                    break;
                case 'news':
                    $sitemapService->generateNewsSitemap($this->database);
                    break;
                case 'posts':
                    $sitemapService->generatePostsSitemap($this->database);
                    break;
            }

            Log::info("Sitemap generated successfully", [
                'type' => $this->type,
                'database' => $this->database
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to generate sitemap", [
                'type' => $this->type,
                'database' => $this->database,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Sitemap generation job failed permanently", [
            'type' => $this->type,
            'database' => $this->database,
            'error' => $exception->getMessage()
        ]);
    }
}
