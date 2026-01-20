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
        Schema::create('post_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id');
            $table->string('platform')->nullable(); // facebook, twitter, whatsapp, copy_link
            $table->string('database')->nullable(); // jo, sa, eg, ps
            $table->timestamps();

            $table->index(['post_id', 'user_id']);
            // We cannot add foreign key for user_id because users are in 'jo' DB and this table might be in 'sa' DB.
            // But post_id is in the same DB.
            // $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_shares');
    }
};
