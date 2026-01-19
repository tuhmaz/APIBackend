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
        Schema::create('cache_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key');
            $table->string('operation'); // hit, miss, set, delete
            $table->integer('response_time_ms')->nullable();
            $table->integer('memory_usage_kb')->nullable();
            $table->integer('ttl')->nullable();
            $table->string('user_id')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['cache_key', 'created_at']);
            $table->index(['operation', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_performance_logs');
    }
};
