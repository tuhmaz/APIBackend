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
        Schema::table('page_visits', function (Blueprint $table) {
            // Change the page_url column to text to accommodate longer URLs
            $table->text('page_url')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('page_visits', function (Blueprint $table) {
            // Change back to string (VARCHAR) if needed
            $table->string('page_url')->change();
        });
    }
};
