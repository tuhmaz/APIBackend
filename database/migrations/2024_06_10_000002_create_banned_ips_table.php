<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('banned_by')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->timestamps();

            $table->index('ip');
            $table->index('banned_until');
            $table->foreign('banned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banned_ips');
    }
};
