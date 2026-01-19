<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connections = ['mysql', 'jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $connection) {
            if (!config("database.connections.$connection")) {
                continue;
            }

            try {
                DB::connection($connection)->getPdo();
            } catch (\Throwable $e) {
                continue;
            }

            if (!Schema::connection($connection)->hasTable('categories')) {
                continue;
            }

            try {
                DB::connection($connection)->statement(
                    'ALTER TABLE categories MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
                );
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), '1075')) {
                    DB::connection($connection)->statement('ALTER TABLE categories ADD PRIMARY KEY (id)');
                    DB::connection($connection)->statement(
                        'ALTER TABLE categories MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
                    );
                } else {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid breaking existing data.
    }
};
