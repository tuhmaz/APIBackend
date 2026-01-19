<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visitors_tracking', function (Blueprint $table) {
            // Change the url column to text to accommodate longer URLs
            $table->text('url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitors_tracking', function (Blueprint $table) {
            // Change back to string (VARCHAR) if needed
            $table->string('url')->nullable()->change();
        });
    }
};
