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
        // تغيير نوع عمود user_agent من string إلى text في جدول visitors_tracking
        Schema::table('visitors_tracking', function (Blueprint $table) {
            $table->text('user_agent')->change();
        });
        
        // تغيير نوع عمود user_agent من string إلى text في جدول security_logs
        Schema::table('security_logs', function (Blueprint $table) {
            $table->text('user_agent')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع عمود user_agent إلى string في جدول visitors_tracking
        Schema::table('visitors_tracking', function (Blueprint $table) {
            $table->string('user_agent')->change();
        });
        
        // إرجاع عمود user_agent إلى string في جدول security_logs
        Schema::table('security_logs', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->change();
        });
    }
};
