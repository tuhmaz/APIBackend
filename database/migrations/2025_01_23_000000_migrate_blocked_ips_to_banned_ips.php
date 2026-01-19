<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // نقل البيانات من blocked_ips إلى banned_ips إذا كانت موجودة
        if (Schema::hasTable('blocked_ips') && Schema::hasTable('banned_ips')) {
            $blockedIps = DB::table('blocked_ips')->get();

            foreach ($blockedIps as $blockedIp) {
                // تحقق من عدم وجود نفس IP في banned_ips
                $exists = DB::table('banned_ips')
                    ->where('ip', $blockedIp->ip_address)
                    ->exists();

                if (!$exists) {
                    DB::table('banned_ips')->insert([
                        'ip' => $blockedIp->ip_address,
                        'reason' => $blockedIp->reason,
                        'banned_by' => $blockedIp->blocked_by,
                        'banned_until' => null, // حظر دائم للبيانات المنقولة
                        'created_at' => $blockedIp->blocked_at ?? $blockedIp->created_at,
                        'updated_at' => $blockedIp->updated_at,
                    ]);
                }
            }

            // حذف جدول blocked_ips القديم بعد نقل البيانات
            Schema::dropIfExists('blocked_ips');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة إنشاء جدول blocked_ips إذا تم التراجع
        if (!Schema::hasTable('blocked_ips')) {
            Schema::create('blocked_ips', function (Blueprint $table) {
                $table->id();
                $table->string('ip_address');
                $table->text('reason')->nullable();
                $table->timestamp('blocked_at');
                $table->foreignId('blocked_by')->constrained('users');
                $table->timestamps();
            });
        }
    }
};
