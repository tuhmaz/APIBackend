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
        Schema::table('notifications', function (Blueprint $table) {
            // Index for read_at to speed up unread notifications count
            $table->index('read_at', 'notifications_read_at_index');

            // Index for created_at to speed up ordering
            $table->index('created_at', 'notifications_created_at_index');

            // Composite index for common queries (user notifications ordered by date)
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'notifications_user_created_index');

            // Composite index for unread notifications
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'notifications_user_unread_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_read_at_index');
            $table->dropIndex('notifications_created_at_index');
            $table->dropIndex('notifications_user_created_index');
            $table->dropIndex('notifications_user_unread_index');
        });
    }
};
