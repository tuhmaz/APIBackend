<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('eg')->table('files', function (Blueprint $table) {
            if (!Schema::connection('eg')->hasColumn('files', 'post_id')) {
                $table->unsignedBigInteger('post_id')->nullable()->after('article_id');
                $table->index('post_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('eg')->table('files', function (Blueprint $table) {
            if (Schema::connection('eg')->hasColumn('files', 'post_id')) {
                $table->dropIndex(['post_id']);
                $table->dropColumn('post_id');
            }
        });
    }
};
