<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Saudi Arabia connection (sa)
        DB::connection('sa')->statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::connection('sa')->statement('ALTER TABLE `files` MODIFY `article_id` BIGINT UNSIGNED NOT NULL');
    }
};
