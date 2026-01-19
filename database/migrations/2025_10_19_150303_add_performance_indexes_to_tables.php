<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة indexes لتحسين الأداء في الاستعلامات الشائعة
     * آمنة للتشغيل على Production - لا تحذف أو تعدل بيانات
     */
    public function up(): void
    {
        // فحص الاتصالات المتاحة
        $connections = ['mysql', 'jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $connection) {
            try {
                // تخطي إذا كان الاتصال غير موجود
                if (!config("database.connections.$connection")) {
                    continue;
                }

                // جدول banned_ips - تحسين استعلامات التحقق من الحظر
                if (Schema::connection($connection)->hasTable('banned_ips')) {
                    Schema::connection($connection)->table('banned_ips', function (Blueprint $table) {
                        // index على ip للبحث السريع
                        if (!$this->indexExists($table->getTable(), 'banned_ips_ip_index', $table->getConnection()->getName())) {
                            $table->index('ip', 'banned_ips_ip_index');
                        }

                        // index على banned_until للبحث عن الحظر النشط/المنتهي
                        if (!$this->indexExists($table->getTable(), 'banned_ips_banned_until_index', $table->getConnection()->getName())) {
                            $table->index('banned_until', 'banned_ips_banned_until_index');
                        }

                        // composite index للاستعلامات المركبة (ip + banned_until)
                        if (!$this->indexExists($table->getTable(), 'banned_ips_ip_banned_until_index', $table->getConnection()->getName())) {
                            $table->index(['ip', 'banned_until'], 'banned_ips_ip_banned_until_index');
                        }
                    });
                }

                // جدول articles - تحسين استعلامات البحث والعرض
                if (Schema::connection($connection)->hasTable('articles')) {
                    Schema::connection($connection)->table('articles', function (Blueprint $table) {
                        // index على status للبحث عن المقالات المنشورة
                        if (!$this->indexExists($table->getTable(), 'articles_status_index', $table->getConnection()->getName())) {
                            $table->index('status', 'articles_status_index');
                        }

                        // index على created_at للترتيب
                        if (!$this->indexExists($table->getTable(), 'articles_created_at_index', $table->getConnection()->getName())) {
                            $table->index('created_at', 'articles_created_at_index');
                        }

                        // composite index للاستعلامات الشائعة (grade_level + subject_id + semester_id)
                        if (!$this->indexExists($table->getTable(), 'articles_grade_subject_semester_index', $table->getConnection()->getName())) {
                            $table->index(['grade_level', 'subject_id', 'semester_id'], 'articles_grade_subject_semester_index');
                        }
                    });
                }

                // جدول posts - تحسين استعلامات البحث والعرض
                if (Schema::connection($connection)->hasTable('posts')) {
                    Schema::connection($connection)->table('posts', function (Blueprint $table) {
                        // index على is_active للبحث عن البوستات النشطة
                        if (!$this->indexExists($table->getTable(), 'posts_is_active_index', $table->getConnection()->getName())) {
                            $table->index('is_active', 'posts_is_active_index');
                        }

                        // index على created_at للترتيب (الأحدث أولاً)
                        if (!$this->indexExists($table->getTable(), 'posts_created_at_index', $table->getConnection()->getName())) {
                            $table->index('created_at', 'posts_created_at_index');
                        }

                        // index على category_id للبحث حسب الفئة
                        if (!$this->indexExists($table->getTable(), 'posts_category_id_index', $table->getConnection()->getName())) {
                            $table->index('category_id', 'posts_category_id_index');
                        }

                        // composite index للاستعلامات الشائعة (is_active + created_at)
                        if (!$this->indexExists($table->getTable(), 'posts_active_created_index', $table->getConnection()->getName())) {
                            $table->index(['is_active', 'created_at'], 'posts_active_created_index');
                        }

                        // composite index (category_id + is_active + created_at)
                        if (!$this->indexExists($table->getTable(), 'posts_category_active_created_index', $table->getConnection()->getName())) {
                            $table->index(['category_id', 'is_active', 'created_at'], 'posts_category_active_created_index');
                        }
                    });
                }

                // جدول activity_log - تحسين استعلامات السجلات
                if (Schema::connection($connection)->hasTable('activity_log')) {
                    Schema::connection($connection)->table('activity_log', function (Blueprint $table) {
                        // composite index على causer (المستخدم الذي قام بالإجراء)
                        if (!$this->indexExists($table->getTable(), 'activity_log_causer_index', $table->getConnection()->getName())) {
                            $table->index(['causer_type', 'causer_id'], 'activity_log_causer_index');
                        }

                        // composite index على subject (الكائن المستهدف)
                        if (!$this->indexExists($table->getTable(), 'activity_log_subject_index', $table->getConnection()->getName())) {
                            $table->index(['subject_type', 'subject_id'], 'activity_log_subject_index');
                        }
                    });
                }

                // جدول sessions - تحسين تنظيف الجلسات القديمة
                if (Schema::connection($connection)->hasTable('sessions')) {
                    Schema::connection($connection)->table('sessions', function (Blueprint $table) {
                        // last_activity مُفَهْرَس بالفعل في الجدول الأساسي
                        // لكن نضيف user_id إذا لم يكن مُفهرساً
                        if (!$this->indexExists($table->getTable(), 'sessions_user_id_index', $table->getConnection()->getName())) {
                            $table->index('user_id', 'sessions_user_id_index');
                        }
                    });
                }

            } catch (\Exception $e) {
                // تسجيل الخطأ والمتابعة للاتصال التالي
                \Illuminate\Support\Facades\Log::warning("Failed to add indexes to connection {$connection}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connections = ['mysql', 'jo', 'sa', 'eg', 'ps'];

        foreach ($connections as $connection) {
            try {
                if (!config("database.connections.$connection")) {
                    continue;
                }

                // حذف indexes من banned_ips
                if (Schema::connection($connection)->hasTable('banned_ips')) {
                    Schema::connection($connection)->table('banned_ips', function (Blueprint $table) {
                        $table->dropIndex('banned_ips_ip_index');
                        $table->dropIndex('banned_ips_banned_until_index');
                        $table->dropIndex('banned_ips_ip_banned_until_index');
                    });
                }

                // حذف indexes من articles
                if (Schema::connection($connection)->hasTable('articles')) {
                    Schema::connection($connection)->table('articles', function (Blueprint $table) {
                        $table->dropIndex('articles_status_index');
                        $table->dropIndex('articles_created_at_index');
                        $table->dropIndex('articles_grade_subject_semester_index');
                    });
                }

                // حذف indexes من posts
                if (Schema::connection($connection)->hasTable('posts')) {
                    Schema::connection($connection)->table('posts', function (Blueprint $table) {
                        $table->dropIndex('posts_is_active_index');
                        $table->dropIndex('posts_created_at_index');
                        $table->dropIndex('posts_category_id_index');
                        $table->dropIndex('posts_active_created_index');
                        $table->dropIndex('posts_category_active_created_index');
                    });
                }

                // حذف indexes من activity_log
                if (Schema::connection($connection)->hasTable('activity_log')) {
                    Schema::connection($connection)->table('activity_log', function (Blueprint $table) {
                        $table->dropIndex('activity_log_causer_index');
                        $table->dropIndex('activity_log_subject_index');
                    });
                }

                // حذف indexes من sessions
                if (Schema::connection($connection)->hasTable('sessions')) {
                    Schema::connection($connection)->table('sessions', function (Blueprint $table) {
                        $table->dropIndex('sessions_user_id_index');
                    });
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to drop indexes from connection {$connection}: " . $e->getMessage());
            }
        }
    }

    /**
     * التحقق من وجود index
     */
    private function indexExists(string $table, string $indexName, string $connection = null): bool
    {
        $conn = $connection ? \Illuminate\Support\Facades\DB::connection($connection) : \Illuminate\Support\Facades\DB::connection();
        $indexes = $conn->getDoctrineSchemaManager()->listTableIndexes($table);

        return isset($indexes[$indexName]);
    }
};
