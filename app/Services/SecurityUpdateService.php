<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class SecurityUpdateService
{
    /**
     * مدة صلاحية ذاكرة التخزين المؤقت للتحديثات
     *
     * @var int
     */
    protected $cacheExpiration = 1440; // 24 ساعة بالدقائق

    /**
     * التحقق من وجود تحديثات أمنية للتطبيق والمكتبات
     *
     * @return array
     */
    public function checkForSecurityUpdates(): array
    {
        return Cache::remember('security_updates_check', $this->cacheExpiration, function () {
            $results = [
                'laravel_version' => $this->checkLaravelUpdates(),
                'php_version' => $this->checkPhpUpdates(),
                'packages' => $this->checkPackageUpdates(),
                'last_check' => now()->format('Y-m-d H:i:s'),
                'has_updates' => false,
                'critical_updates' => false,
            ];

            // تحديد ما إذا كانت هناك تحديثات متاحة
            if ($results['laravel_version']['has_update'] || 
                $results['php_version']['has_update'] || 
                count($results['packages']['outdated']) > 0) {
                $results['has_updates'] = true;
            }

            // تحديد ما إذا كانت هناك تحديثات حرجة
            if ($results['laravel_version']['is_critical'] || 
                $results['php_version']['is_critical'] || 
                count($results['packages']['security_updates']) > 0) {
                $results['critical_updates'] = true;
            }

            return $results;
        });
    }

    /**
     * التحقق من وجود تحديثات لإطار العمل Laravel
     *
     * @return array
     */
    protected function checkLaravelUpdates(): array
    {
        $currentVersion = app()->version();
        $result = [
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'has_update' => false,
            'is_critical' => false,
            'update_url' => 'https://github.com/laravel/laravel/releases',
        ];

        try {
            // الحصول على أحدث إصدار من Laravel
            $response = Http::get('https://packagist.org/packages/laravel/framework.json');
            
            if ($response->successful()) {
                $data = $response->json();
                $versions = array_keys($data['package']['versions']);
                
                // استبعاد الإصدارات التجريبية وإصدارات RC
                $stableVersions = array_filter($versions, function ($version) {
                    return !str_contains($version, 'dev') && 
                           !str_contains($version, 'RC') && 
                           !str_contains($version, 'beta') && 
                           !str_contains($version, 'alpha');
                });
                
                if (!empty($stableVersions)) {
                    // الحصول على أحدث إصدار مستقر
                    $latestVersion = $stableVersions[0];
                    $result['latest_version'] = $latestVersion;
                    
                    // التحقق مما إذا كان الإصدار الحالي أقدم من أحدث إصدار
                    $currentMajor = (int) explode('.', $currentVersion)[0];
                    $latestMajor = (int) explode('.', $latestVersion)[0];
                    
                    if (version_compare($currentVersion, $latestVersion, '<')) {
                        $result['has_update'] = true;
                        
                        // التحقق مما إذا كان التحديث حرجًا (تغيير في الإصدار الرئيسي أو الثانوي)
                        if ($currentMajor < $latestMajor) {
                            $result['is_critical'] = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('فشل في التحقق من تحديثات Laravel: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * التحقق من وجود تحديثات للغة PHP
     *
     * @return array
     */
    protected function checkPhpUpdates(): array
    {
        $currentVersion = PHP_VERSION;
        $result = [
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'has_update' => false,
            'is_critical' => false,
            'update_url' => 'https://www.php.net/downloads.php',
        ];

        try {
            // الحصول على أحدث إصدارات PHP
            $response = Http::get('https://www.php.net/releases/index.php?json=1');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data)) {
                    // الحصول على أحدث إصدار
                    $latestVersion = array_key_first($data);
                    $result['latest_version'] = $latestVersion;
                    
                    // التحقق مما إذا كان الإصدار الحالي أقدم من أحدث إصدار
                    $currentMajor = (int) explode('.', $currentVersion)[0];
                    $currentMinor = (int) explode('.', $currentVersion)[1];
                    $latestMajor = (int) explode('.', $latestVersion)[0];
                    $latestMinor = (int) explode('.', $latestVersion)[1];
                    
                    if (version_compare($currentVersion, $latestVersion, '<')) {
                        $result['has_update'] = true;
                        
                        // التحقق مما إذا كان التحديث حرجًا (تغيير في الإصدار الرئيسي أو الثانوي)
                        if ($currentMajor < $latestMajor || ($currentMajor == $latestMajor && $currentMinor < $latestMinor)) {
                            $result['is_critical'] = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('فشل في التحقق من تحديثات PHP: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * التحقق من وجود تحديثات للحزم والمكتبات
     *
     * @return array
     */
    protected function checkPackageUpdates(): array
    {
        $result = [
            'total_packages' => 0,
            'outdated' => [],
            'security_updates' => [],
        ];

        try {
            // التحقق من وجود ملف composer.json
            if (File::exists(base_path('composer.json'))) {
                // قراءة ملف composer.json
                $composerJson = json_decode(File::get(base_path('composer.json')), true);
                
                if (isset($composerJson['require'])) {
                    $result['total_packages'] = count($composerJson['require']);
                    
                    // الحصول على قائمة الحزم المثبتة
                    $installedPackages = $this->getInstalledPackages();
                    
                    // التحقق من كل حزمة
                    foreach ($composerJson['require'] as $package => $version) {
                        // تجاهل حزم PHP وext-*
                        if ($package === 'php' || strpos($package, 'ext-') === 0) {
                            continue;
                        }
                        
                        // التحقق من وجود تحديثات للحزمة
                        $packageInfo = $this->checkPackageVersion($package, $installedPackages[$package] ?? null);
                        
                        if ($packageInfo['has_update']) {
                            $result['outdated'][] = $packageInfo;
                            
                            // التحقق مما إذا كان التحديث أمنيًا
                            if ($packageInfo['is_security_update']) {
                                $result['security_updates'][] = $packageInfo;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('فشل في التحقق من تحديثات الحزم: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * الحصول على قائمة الحزم المثبتة
     *
     * @return array
     */
    protected function getInstalledPackages(): array
    {
        $packages = [];
        
        try {
            // التحقق من وجود ملف composer.lock
            if (File::exists(base_path('composer.lock'))) {
                // قراءة ملف composer.lock
                $composerLock = json_decode(File::get(base_path('composer.lock')), true);
                
                if (isset($composerLock['packages'])) {
                    foreach ($composerLock['packages'] as $package) {
                        if (isset($package['name']) && isset($package['version'])) {
                            $packages[$package['name']] = $package['version'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('فشل في قراءة الحزم المثبتة: ' . $e->getMessage());
        }
        
        return $packages;
    }

    /**
     * التحقق من وجود تحديثات لحزمة معينة
     *
     * @param string $package
     * @param string|null $currentVersion
     * @return array
     */
    protected function checkPackageVersion(string $package, ?string $currentVersion): array
    {
        $result = [
            'name' => $package,
            'current_version' => $currentVersion ?? 'unknown',
            'latest_version' => $currentVersion ?? 'unknown',
            'has_update' => false,
            'is_security_update' => false,
            'update_url' => "https://packagist.org/packages/{$package}",
        ];
        
        try {
            // الحصول على معلومات الحزمة من Packagist
            $response = Http::get("https://packagist.org/packages/{$package}.json");
            
            if ($response->successful() && $currentVersion) {
                $data = $response->json();
                
                if (isset($data['package']['versions'])) {
                    $versions = array_keys($data['package']['versions']);
                    
                    // استبعاد الإصدارات التجريبية وإصدارات RC
                    $stableVersions = array_filter($versions, function ($version) {
                        return !str_contains($version, 'dev') && 
                               !str_contains($version, 'RC') && 
                               !str_contains($version, 'beta') && 
                               !str_contains($version, 'alpha');
                    });
                    
                    if (!empty($stableVersions)) {
                        // الحصول على أحدث إصدار مستقر
                        $latestVersion = $stableVersions[0];
                        $result['latest_version'] = $latestVersion;
                        
                        // التحقق مما إذا كان الإصدار الحالي أقدم من أحدث إصدار
                        if (version_compare(preg_replace('/[^0-9.]/', '', $currentVersion), preg_replace('/[^0-9.]/', '', $latestVersion), '<')) {
                            $result['has_update'] = true;
                            
                            // التحقق مما إذا كان التحديث يتضمن إصلاحات أمنية
                            // نبحث في وصف الإصدار عن كلمات مفتاحية تشير إلى تحديثات أمنية
                            if (isset($data['package']['versions'][$latestVersion]['description'])) {
                                $description = strtolower($data['package']['versions'][$latestVersion]['description']);
                                
                                if (str_contains($description, 'security') || 
                                    str_contains($description, 'vulnerability') || 
                                    str_contains($description, 'exploit') || 
                                    str_contains($description, 'fix') || 
                                    str_contains($description, 'patch')) {
                                    $result['is_security_update'] = true;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("فشل في التحقق من إصدار الحزمة {$package}: " . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * تنفيذ تحديث الحزم
     *
     * @param bool $onlySecurityUpdates
     * @return array
     */
    public function updatePackages(bool $onlySecurityUpdates = false): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'updated_packages' => [],
        ];
        
        try {
            // التحقق من التحديثات المتاحة
            $updates = $this->checkForSecurityUpdates();
            
            if ($updates['has_updates']) {
                $packagesToUpdate = [];
                
                if ($onlySecurityUpdates) {
                    // تحديث الحزم التي تحتوي على إصلاحات أمنية فقط
                    foreach ($updates['packages']['security_updates'] as $package) {
                        $packagesToUpdate[] = $package['name'];
                    }
                } else {
                    // تحديث جميع الحزم القديمة
                    foreach ($updates['packages']['outdated'] as $package) {
                        $packagesToUpdate[] = $package['name'];
                    }
                }
                
                if (!empty($packagesToUpdate)) {
                    // تنفيذ أمر التحديث
                    $packagesString = implode(' ', $packagesToUpdate);
                    $command = "composer update {$packagesString} --with-dependencies";
                    
                    // تسجيل الأمر قبل تنفيذه
                    Log::info("تنفيذ تحديث الحزم: {$command}");
                    
                    // تنفيذ الأمر
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        $result['success'] = true;
                        $result['message'] = 'تم تحديث الحزم بنجاح';
                        $result['updated_packages'] = $packagesToUpdate;
                        
                        // مسح ذاكرة التخزين المؤقت للتحديثات
                        Cache::forget('security_updates_check');
                    } else {
                        $result['message'] = 'فشل في تحديث الحزم: ' . implode("\n", $output);
                    }
                } else {
                    $result['message'] = 'لا توجد حزم للتحديث';
                    $result['success'] = true;
                }
            } else {
                $result['message'] = 'جميع الحزم محدثة';
                $result['success'] = true;
            }
        } catch (\Exception $e) {
            $result['message'] = 'حدث خطأ أثناء تحديث الحزم: ' . $e->getMessage();
            Log::error($result['message']);
        }
        
        return $result;
    }

    /**
     * تنفيذ فحص أمني للتطبيق
     *
     * @return array
     */
    public function runSecurityScan(): array
    {
        $result = [
            'success' => false,
            'vulnerabilities' => [],
            'scan_date' => now()->format('Y-m-d H:i:s'),
            'message' => '',
        ];
        
        try {
            // فحص تكوين التطبيق
            $configIssues = $this->scanAppConfiguration();
            
            // فحص أذونات الملفات
            $permissionIssues = $this->scanFilePermissions();
            
            // فحص الثغرات الأمنية المعروفة
            $knownVulnerabilities = $this->scanKnownVulnerabilities();
            
            // جمع جميع المشكلات
            $result['vulnerabilities'] = array_merge(
                $configIssues,
                $permissionIssues,
                $knownVulnerabilities
            );
            
            $result['success'] = true;
            $result['total_issues'] = count($result['vulnerabilities']);
            $result['message'] = 'تم إكمال الفحص الأمني بنجاح';
        } catch (\Exception $e) {
            $result['message'] = 'حدث خطأ أثناء الفحص الأمني: ' . $e->getMessage();
            Log::error($result['message']);
        }
        
        return $result;
    }

    /**
     * فحص تكوين التطبيق
     *
     * @return array
     */
    protected function scanAppConfiguration(): array
    {
        $issues = [];
        
        // التحقق من وضع التصحيح
        if (config('app.debug') === true) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'high',
                'description' => 'وضع التصحيح مفعل في الإنتاج',
                'recommendation' => 'قم بتعطيل وضع التصحيح في بيئة الإنتاج عن طريق تعيين APP_DEBUG=false في ملف .env',
            ];
        }
        
        // التحقق من مفتاح التطبيق
        if (strlen(config('app.key')) < 32) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'critical',
                'description' => 'مفتاح التطبيق غير آمن أو غير موجود',
                'recommendation' => 'قم بإنشاء مفتاح تطبيق جديد باستخدام الأمر php artisan key:generate',
            ];
        }
        
        // التحقق من تكوين CORS
        if (config('cors.allowed_origins') === ['*']) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'medium',
                'description' => 'تكوين CORS يسمح بالوصول من أي أصل',
                'recommendation' => 'قيد الأصول المسموح بها في تكوين CORS إلى النطاقات المحددة التي تحتاج إلى الوصول',
            ];
        }
        
        // التحقق من تكوين ملفات تعريف الارتباط
        if (config('session.secure') === false) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'medium',
                'description' => 'ملفات تعريف الارتباط غير مؤمنة (بدون علامة secure)',
                'recommendation' => 'قم بتمكين ملفات تعريف الارتباط الآمنة عن طريق تعيين SESSION_SECURE_COOKIE=true في ملف .env',
            ];
        }
        
        return $issues;
    }

    /**
     * فحص أذونات الملفات
     *
     * @return array
     */
    protected function scanFilePermissions(): array
    {
        $issues = [];
        
        // قائمة المجلدات التي يجب أن تكون قابلة للكتابة
        $writableDirs = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
        
        foreach ($writableDirs as $dir) {
            if (!is_writable($dir)) {
                $issues[] = [
                    'type' => 'permission',
                    'severity' => 'high',
                    'description' => "المجلد {$dir} غير قابل للكتابة",
                    'recommendation' => "تأكد من أن المجلد {$dir} قابل للكتابة بواسطة مستخدم خادم الويب",
                ];
            }
        }
        
        // التحقق من أذونات ملف .env
        $envFile = base_path('.env');
        if (file_exists($envFile)) {
            $perms = fileperms($envFile);
            
            // التحقق من أن الملف غير قابل للقراءة للآخرين
            if (($perms & 0x0004) || ($perms & 0x0002)) {
                $issues[] = [
                    'type' => 'permission',
                    'severity' => 'critical',
                    'description' => 'ملف .env قابل للقراءة أو الكتابة للآخرين',
                    'recommendation' => 'قم بتغيير أذونات ملف .env إلى 0600 أو 0640',
                ];
            }
        }
        
        return $issues;
    }

    /**
     * فحص الثغرات الأمنية المعروفة
     *
     * @return array
     */
    protected function scanKnownVulnerabilities(): array
    {
        $issues = [];
        
        // التحقق من وجود ملفات حساسة متاحة للجمهور
        $sensitiveFiles = [
            public_path('.env'),
            public_path('composer.json'),
            public_path('composer.lock'),
            public_path('.git'),
            public_path('storage'),
        ];
        
        foreach ($sensitiveFiles as $file) {
            if (file_exists($file)) {
                $issues[] = [
                    'type' => 'vulnerability',
                    'severity' => 'critical',
                    'description' => "الملف أو المجلد الحساس {$file} متاح للجمهور",
                    'recommendation' => "قم بإزالة أو حظر الوصول إلى {$file} من المجلد العام",
                ];
            }
        }
        
        // التحقق من وجود إعدادات افتراضية غير آمنة
        if (env('DB_PASSWORD') === 'password' || env('DB_PASSWORD') === '') {
            $issues[] = [
                'type' => 'vulnerability',
                'severity' => 'critical',
                'description' => 'كلمة مرور قاعدة البيانات ضعيفة أو فارغة',
                'recommendation' => 'قم بتعيين كلمة مرور قوية لقاعدة البيانات في ملف .env',
            ];
        }
        
        return $issues;
    }

    /**
     * الحصول على تقرير أمني شامل
     *
     * @return array
     */
    public function getSecurityReport(): array
    {
        // التحقق من وجود تحديثات
        $updates = $this->checkForSecurityUpdates();
        
        // تنفيذ فحص أمني
        $scan = $this->runSecurityScan();
        
        // الحصول على إحصائيات سجلات الأمان
        $logStats = $this->getSecurityLogStats();
        
        return [
            'updates' => $updates,
            'scan' => $scan,
            'logs' => $logStats,
            'report_date' => now()->format('Y-m-d H:i:s'),
            'overall_security_score' => $this->calculateOverallSecurityScore($updates, $scan, $logStats),
        ];
    }

    /**
     * الحصول على إحصائيات سجلات الأمان
     *
     * @return array
     */
    protected function getSecurityLogStats(): array
    {
        $result = [
            'total_logs' => 0,
            'critical_logs' => 0,
            'unresolved_issues' => 0,
            'recent_suspicious_activity' => 0,
        ];
        
        try {
            // إجمالي السجلات
            $result['total_logs'] = \App\Models\SecurityLog::count();
            
            // السجلات الحرجة
            $result['critical_logs'] = \App\Models\SecurityLog::where('severity', \App\Models\SecurityLog::SEVERITY_LEVELS['CRITICAL'])->count();
            
            // المشكلات غير المحلولة
            $result['unresolved_issues'] = \App\Models\SecurityLog::where('is_resolved', false)->count();
            
            // النشاط المشبوه الأخير
            $result['recent_suspicious_activity'] = \App\Models\SecurityLog::where('event_type', \App\Models\SecurityLog::EVENT_TYPES['SUSPICIOUS_ACTIVITY'])
                ->where('created_at', '>=', now()->subDay())
                ->count();
        } catch (\Exception $e) {
            Log::error('فشل في الحصول على إحصائيات سجلات الأمان: ' . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * حساب درجة الأمان الإجمالية
     *
     * @param array $updates
     * @param array $scan
     * @param array $logStats
     * @return int
     */
    protected function calculateOverallSecurityScore(array $updates, array $scan, array $logStats): int
    {
        $score = 100;
        
        // خصم النقاط بناءً على التحديثات المتاحة
        if ($updates['critical_updates']) {
            $score -= 30;
        } elseif ($updates['has_updates']) {
            $score -= 10;
        }
        
        // خصم النقاط بناءً على نتائج الفحص
        if (!empty($scan['vulnerabilities'])) {
            $criticalIssues = 0;
            $highIssues = 0;
            $mediumIssues = 0;
            
            foreach ($scan['vulnerabilities'] as $issue) {
                if ($issue['severity'] === 'critical') {
                    $criticalIssues++;
                } elseif ($issue['severity'] === 'high') {
                    $highIssues++;
                } elseif ($issue['severity'] === 'medium') {
                    $mediumIssues++;
                }
            }
            
            $score -= ($criticalIssues * 15 + $highIssues * 10 + $mediumIssues * 5);
        }
        
        // خصم النقاط بناءً على سجلات الأمان
        if ($logStats['critical_logs'] > 0) {
            $score -= min(20, $logStats['critical_logs'] * 2);
        }
        
        if ($logStats['unresolved_issues'] > 0) {
            $score -= min(15, $logStats['unresolved_issues']);
        }
        
        if ($logStats['recent_suspicious_activity'] > 0) {
            $score -= min(10, $logStats['recent_suspicious_activity'] * 2);
        }
        
        // ضمان أن الدرجة في النطاق من 0 إلى 100
        return max(0, min(100, $score));
    }
}
