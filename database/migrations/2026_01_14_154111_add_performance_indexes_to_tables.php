<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add performance indexes to improve query speed
     */
    public function up(): void
    {
        // Posts table indexes
        Schema::table('posts', function (Blueprint $table) {
            // Check and add indexes only if they don't exist
            $this->addIndexIfNotExists('posts', ['category_id'], 'posts_category_id_index');
            $this->addIndexIfNotExists('posts', ['is_active'], 'posts_is_active_index');
            $this->addIndexIfNotExists('posts', ['is_featured'], 'posts_is_featured_index');
            $this->addIndexIfNotExists('posts', ['category_id', 'is_active', 'created_at'], 'posts_category_active_index');
            $this->addIndexIfNotExists('posts', ['slug'], 'posts_slug_index');
            $this->addIndexIfNotExists('posts', ['views'], 'posts_views_index');
        });

        // Articles table indexes
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $this->addIndexIfNotExists('articles', ['school_class_id'], 'articles_school_class_id_index');
                $this->addIndexIfNotExists('articles', ['subject_id'], 'articles_subject_id_index');
                $this->addIndexIfNotExists('articles', ['semester_id'], 'articles_semester_id_index');
                $this->addIndexIfNotExists('articles', ['category_id'], 'articles_category_id_index');
                $this->addIndexIfNotExists('articles', ['school_class_id', 'subject_id', 'semester_id'], 'articles_class_subject_semester_index');
            });
        }

        // Files table indexes
        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                $this->addIndexIfNotExists('files', ['article_id'], 'files_article_id_index');
                $this->addIndexIfNotExists('files', ['type'], 'files_type_index');
                $this->addIndexIfNotExists('files', ['school_class_id'], 'files_school_class_id_index');
            });
        }

        // Categories table indexes
        Schema::table('categories', function (Blueprint $table) {
            $this->addIndexIfNotExists('categories', ['slug'], 'categories_slug_index');
            $this->addIndexIfNotExists('categories', ['is_active'], 'categories_is_active_index');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $this->addIndexIfNotExists('users', ['email'], 'users_email_index');
            $this->addIndexIfNotExists('users', ['is_online'], 'users_is_online_index');
            $this->addIndexIfNotExists('users', ['last_activity'], 'users_last_activity_index');
        });

        // Comments table indexes (if exists)
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $this->addIndexIfNotExists('comments', ['post_id'], 'comments_post_id_index');
                $this->addIndexIfNotExists('comments', ['user_id'], 'comments_user_id_index');
                $this->addIndexIfNotExists('comments', ['created_at'], 'comments_created_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop posts indexes
        Schema::table('posts', function (Blueprint $table) {
            $this->dropIndexIfExists('posts', 'posts_category_id_index');
            $this->dropIndexIfExists('posts', 'posts_is_active_index');
            $this->dropIndexIfExists('posts', 'posts_is_featured_index');
            $this->dropIndexIfExists('posts', 'posts_category_active_index');
            $this->dropIndexIfExists('posts', 'posts_slug_index');
            $this->dropIndexIfExists('posts', 'posts_views_index');
        });

        // Drop articles indexes
        if (Schema::hasTable('articles')) {
            Schema::table('articles', function (Blueprint $table) {
                $this->dropIndexIfExists('articles', 'articles_school_class_id_index');
                $this->dropIndexIfExists('articles', 'articles_subject_id_index');
                $this->dropIndexIfExists('articles', 'articles_semester_id_index');
                $this->dropIndexIfExists('articles', 'articles_category_id_index');
                $this->dropIndexIfExists('articles', 'articles_class_subject_semester_index');
            });
        }

        // Drop files indexes
        if (Schema::hasTable('files')) {
            Schema::table('files', function (Blueprint $table) {
                $this->dropIndexIfExists('files', 'files_article_id_index');
                $this->dropIndexIfExists('files', 'files_type_index');
                $this->dropIndexIfExists('files', 'files_school_class_id_index');
            });
        }

        // Drop categories indexes
        Schema::table('categories', function (Blueprint $table) {
            $this->dropIndexIfExists('categories', 'categories_slug_index');
            $this->dropIndexIfExists('categories', 'categories_is_active_index');
        });

        // Drop users indexes
        Schema::table('users', function (Blueprint $table) {
            $this->dropIndexIfExists('users', 'users_email_index');
            $this->dropIndexIfExists('users', 'users_is_online_index');
            $this->dropIndexIfExists('users', 'users_last_activity_index');
        });

        // Drop comments indexes
        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $this->dropIndexIfExists('comments', 'comments_post_id_index');
                $this->dropIndexIfExists('comments', 'comments_user_id_index');
                $this->dropIndexIfExists('comments', 'comments_created_at_index');
            });
        }
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` (`" . implode('`, `', $columns) . "`)");
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");
        return count($indexes) > 0;
    }
};
