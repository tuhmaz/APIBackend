<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );
        return !empty($rows);
    }

    public function up(): void
    {
        // Add columns/indexes if they don't exist
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'depth')) {
                $table->unsignedTinyInteger('depth')->default(0)->after('country');
            }
            // These index() calls are safe even if already present (Laravel will name them automatically)
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            }
        });

        // Add indexes only if missing
        if (!$this->indexExists('categories', 'categories_parent_id_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('parent_id');
            });
        }
        if (!$this->indexExists('categories', 'categories_country_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('country');
            });
        }

        // Backfill depth values
        $categories = DB::table('categories')->select('id', 'parent_id', 'depth')->get()->keyBy('id');

        // Simple memoized DFS to compute depth
        $computed = [];
        $visiting = [];
        $computeDepth = function ($id) use (&$computeDepth, &$categories, &$computed, &$visiting) {
            if (isset($computed[$id])) return $computed[$id];
            if (isset($visiting[$id])) return 0; // break cycles defensively
            $visiting[$id] = true;
            $node = $categories[$id] ?? null;
            if (!$node || !$node->parent_id) {
                return $computed[$id] = 0;
            }
            $parentDepth = $computeDepth($node->parent_id);
            return $computed[$id] = min(255, $parentDepth + 1);
        };

        $updates = [];
        foreach ($categories as $id => $row) {
            $depth = $computeDepth($id);
            if ((int)($row->depth ?? -1) !== $depth) {
                $updates[$id] = $depth;
            }
        }

        if (!empty($updates)) {
            foreach (array_chunk($updates, 1000, true) as $chunk) {
                foreach ($chunk as $id => $depth) {
                    DB::table('categories')->where('id', $id)->update(['depth' => $depth]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'depth')) {
                $table->dropColumn('depth');
            }
        });

        // Drop indexes only if they exist
        if ($this->indexExists('categories', 'categories_parent_id_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_parent_id_index');
            });
        }
        if ($this->indexExists('categories', 'categories_country_index')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_country_index');
            });
        }
    }
};
