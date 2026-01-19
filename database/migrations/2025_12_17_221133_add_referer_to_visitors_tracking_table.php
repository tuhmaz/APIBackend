<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('visitors_tracking')) {
            return;
        }

        if (!Schema::hasColumn('visitors_tracking', 'referer')) {
            Schema::table('visitors_tracking', function (Blueprint $table) {
                $table->text('referer')->nullable()->after('url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('visitors_tracking')) {
            return;
        }

        if (Schema::hasColumn('visitors_tracking', 'referer')) {
            Schema::table('visitors_tracking', function (Blueprint $table) {
                $table->dropColumn('referer');
            });
        }
    }
};
