<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Egypt connection (eg)
        DB::connection('eg')->statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::connection('eg')->statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NOT NULL');
    }
};
