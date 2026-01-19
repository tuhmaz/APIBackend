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
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();

            // Session and identity
            $table->string('session_id')->index();
            $table->string('ip', 45)->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Location
            $table->string('country')->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Client info
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->text('user_agent')->nullable();

            // Request info
            $table->text('url')->nullable();
            $table->boolean('is_ajax')->default(false);
            $table->boolean('is_desktop')->default(false);
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_bot')->default(false)->index();

            // Activity
            $table->timestamp('last_activity')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_sessions');
    }
};
