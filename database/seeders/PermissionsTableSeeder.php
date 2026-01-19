<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PermissionsTableSeeder extends Seeder
{
  public function run()
  {
    $timestamp = now();

    $permissions = [
      // ============================================
      // 📊 صلاحيات لوحة التحكم الرئيسية
      // ============================================
      ['name' => 'access dashboard', 'guard_name' => 'sanctum'],
      ['name' => 'dashboard.view', 'guard_name' => 'sanctum'],

      // ============================================
      // 👥 صلاحيات إدارة المستخدمين
      // ============================================
      ['name' => 'manage users', 'guard_name' => 'sanctum'],
      ['name' => 'users.view', 'guard_name' => 'sanctum'],
      ['name' => 'users.create', 'guard_name' => 'sanctum'],
      ['name' => 'users.edit', 'guard_name' => 'sanctum'],
      ['name' => 'users.delete', 'guard_name' => 'sanctum'],

      // 🎭 صلاحيات الأدوار والصلاحيات
      ['name' => 'manage roles', 'guard_name' => 'sanctum'],

      ['name' => 'manage permissions', 'guard_name' => 'sanctum'],

      // ============================================
      // 📝 صلاحيات إدارة المحتوى
      // ============================================

      // المقالات
      ['name' => 'manage articles', 'guard_name' => 'sanctum'],

      // المنشورات
      ['name' => 'manage posts', 'guard_name' => 'sanctum'],

      // الفئات
      ['name' => 'manage categories', 'guard_name' => 'sanctum'],

      // ============================================
      // 📁 صلاحيات إدارة الملفات
      // ============================================
      ['name' => 'manage files', 'guard_name' => 'sanctum'],

      // ============================================
      // 💬 صلاحيات التعليقات والتفاعلات
      // ============================================
      ['name' => 'manage comments', 'guard_name' => 'sanctum'],

      // ============================================
      // 🎓 صلاحيات النظام التعليمي
      // ============================================

      // الصفوف الدراسية
      ['name' => 'manage school classes', 'guard_name' => 'sanctum'],

      // المواد الدراسية
      ['name' => 'manage subjects', 'guard_name' => 'sanctum'],

      // الفصول الدراسية
      ['name' => 'manage semesters', 'guard_name' => 'sanctum'],

      // الحضور
      ['name' => 'manage attendance', 'guard_name' => 'sanctum'],

      // ============================================
      // 📧 صلاحيات الرسائل والإشعارات
      // ============================================

      // الرسائل
      ['name' => 'manage messages', 'guard_name' => 'sanctum'],

      // الإشعارات
      ['name' => 'manage notifications', 'guard_name' => 'sanctum'],

      // ============================================
      // 🛡️ صلاحيات الأمان والمراقبة
      // ============================================

      // المراقبة
      ['name' => 'manage monitoring', 'guard_name' => 'sanctum'],

      // الأمان
      ['name' => 'manage security', 'guard_name' => 'sanctum'],

      // الأداء
      ['name' => 'manage performance', 'guard_name' => 'sanctum'],

      // Redis
      ['name' => 'manage redis', 'guard_name' => 'sanctum'],

      // ============================================
      // 📊 صلاحيات التحليلات
      // ============================================
      ['name' => 'manage analytics', 'guard_name' => 'sanctum'],

      // ============================================
      // 📅 صلاحيات التقويم
      // ============================================
      ['name' => 'manage calendar', 'guard_name' => 'sanctum'],

      // ============================================
      // 🗺️ صلاحيات خريطة الموقع
      // ============================================
      ['name' => 'manage sitemap', 'guard_name' => 'sanctum'],

      // ============================================
      // ⚙️ صلاحيات الإعدادات
      // ============================================
      ['name' => 'manage settings', 'guard_name' => 'sanctum'],

      // ============================================
      // 👤 صلاحيات الملف الشخصي
      // ============================================

      // ============================================
      // 🔧 صلاحيات النظام المتقدمة
      // ============================================
      ['name' => 'manage cache', 'guard_name' => 'sanctum'],
      ['name' => 'manage reports', 'guard_name' => 'sanctum'],

      // ============================================
      // 🔙 صلاحيات للتوافق مع النظام القديم
      // ============================================
      ['name' => 'admin users', 'guard_name' => 'sanctum'],
      ['name' => 'view analytics', 'guard_name' => 'sanctum'],
      ['name' => 'manage news', 'guard_name' => 'sanctum'],
      ['name' => 'view messages', 'guard_name' => 'sanctum'],
      ['name' => 'send messages', 'guard_name' => 'sanctum'],
      ['name' => 'view activity', 'guard_name' => 'sanctum'],
      ['name' => 'monitor redis', 'guard_name' => 'sanctum'],
      ['name' => 'view redis stats', 'guard_name' => 'sanctum'],
      ['name' => 'view security', 'guard_name' => 'sanctum'],
      ['name' => 'view security logs', 'guard_name' => 'sanctum'],
      ['name' => 'view security analytics', 'guard_name' => 'sanctum'],
      ['name' => 'manage blocked ips', 'guard_name' => 'sanctum'],
      ['name' => 'manage chating', 'guard_name' => 'sanctum'],
      ['name' => 'legacy', 'guard_name' => 'sanctum'],
    ];

    foreach ($permissions as $permission) {
      // التحقق من وجود الصلاحية قبل إنشائها
      if (!DB::table('permissions')->where('name', $permission['name'])->where('guard_name', $permission['guard_name'])->exists()) {
        $permission['created_at'] = $timestamp;
        $permission['updated_at'] = $timestamp;
        DB::table('permissions')->insert($permission);
      }
    }
  }
}
