<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Make article_id nullable on default connection
        DB::statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // Revert to NOT NULL (may fail if data contains NULLs)
        DB::statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NOT NULL');
    }
};
