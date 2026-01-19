<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected array $connections = ['jo','sa','eg','ps'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            // Skip if database connection doesn't exist
            try {
                \DB::connection($conn)->getPdo();
            } catch (\Throwable $e) {
                continue; // Skip this connection if database doesn't exist
            }

            // articles indexes (wrap each in try/catch to avoid duplicate index errors)
            $this->tryAddIndex($conn, 'articles', ['subject_id']);
            $this->tryAddIndex($conn, 'articles', ['semester_id']);
            $this->tryAddIndex($conn, 'articles', ['grade_level']);
            $this->tryAddIndex($conn, 'articles', ['author_id']);
            $this->tryAddIndex($conn, 'articles', ['status']);

            // files indexes
            $this->tryAddIndex($conn, 'files', ['article_id']);
            $this->tryAddIndex($conn, 'files', ['file_category']);
            $this->tryAddIndex($conn, 'files', ['article_id','file_category']);

            // pivot table article_keyword indexes
            if (Schema::connection($conn)->hasTable('article_keyword')) {
                $this->tryAddIndex($conn, 'article_keyword', ['article_id']);
                $this->tryAddIndex($conn, 'article_keyword', ['keyword_id']);
                $this->tryAddIndex($conn, 'article_keyword', ['article_id','keyword_id']);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            // Skip if database connection doesn't exist
            try {
                \DB::connection($conn)->getPdo();
            } catch (\Throwable $e) {
                continue;
            }

            // Drop created indexes (ignore if missing)
            $this->dropIndexIfExists($conn, 'articles', ['subject_id']);
            $this->dropIndexIfExists($conn, 'articles', ['semester_id']);
            $this->dropIndexIfExists($conn, 'articles', ['grade_level']);
            $this->dropIndexIfExists($conn, 'articles', ['author_id']);
            $this->dropIndexIfExists($conn, 'articles', ['status']);

            $this->dropIndexIfExists($conn, 'files', ['article_id']);
            $this->dropIndexIfExists($conn, 'files', ['file_category']);
            $this->dropIndexIfExists($conn, 'files', ['article_id','file_category']);

            if (Schema::connection($conn)->hasTable('article_keyword')) {
                $this->dropIndexIfExists($conn, 'article_keyword', ['article_id']);
                $this->dropIndexIfExists($conn, 'article_keyword', ['keyword_id']);
                $this->dropIndexIfExists($conn, 'article_keyword', ['article_id','keyword_id']);
            }
        }
    }

    private function tryAddIndex(string $connection, string $table, array $columns): void
    {
        $indexName = $this->indexName($table, $columns);
        try {
            Schema::connection($connection)->table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // ignore duplicate or missing table/column errors during add
        }
    }

    private function dropIndexIfExists(string $connection, string $table, array $columns): void
    {
        $indexName = $this->indexName($table, $columns);
        try {
            Schema::connection($connection)->table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function indexName(string $table, array $columns): string
    {
        return $table.'_'.implode('_', $columns).'_index';
    }
};
