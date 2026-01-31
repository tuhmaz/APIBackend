<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VisitorTracking;
use App\Services\VisitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\BaseResource;

class AnalyticsApiController extends Controller
{
    protected $visitorService;

    public function __construct(VisitorService $visitorService)
    {
        $this->visitorService = $visitorService;
    }

    /**
     * GET /api/analytics
     * نقطة الدخول الرئيسية للتحليلات
     */
    public function index(Request $request)
    {
        $visitorOptions = $this->parseVisitorOptions($request);
        return new BaseResource([
            'visitor_stats'   => $this->getVisitorStats($visitorOptions),
            'user_stats'      => $this->getUserStats(),
            'country_stats'   => $this->getCountryStats(),
            'chart_data'      => $this->getChartData(),
            'device_stats'    => $this->getDeviceStats(),
            'traffic_sources' => $this->getTrafficSources(),
        ]);
    }

    /**
     * إحصائيات الرسم البياني (زوار ومشاهدات) لآخر 30 يوم
     */
    protected function getChartData()
    {
        $days = 30;
        $endDate = now();
        $startDate = now()->subDays($days);

        // Visitors (Sessions)
        $visitors = DB::connection('jo')->table('visitors_tracking')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Page Views
        $pageViews = DB::connection('jo')->table('page_visits')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        $data = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        // Arabic Month Names
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس', 
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $day = $date->format('d');
            $month = $months[$date->format('n')];
            $displayDate = "$day $month";

            $data[] = [
                'name' => $displayDate,
                'full_date' => $formattedDate,
                'visitors' => $visitors[$formattedDate] ?? 0,
                'pageViews' => $pageViews[$formattedDate] ?? 0,
            ];
        }

        return $data;
    }

    /**
     * إحصائيات الأجهزة
     */
    protected function getDeviceStats()
    {
        // Group by OS as a proxy for device type
        $stats = DB::connection('jo')->table('visitors_tracking')
            ->select('os', DB::raw('count(*) as count'))
            ->whereNotNull('os')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('os')
            ->orderByDesc('count')
            ->get();

        $devices = [
            'Desktop' => 0,
            'Mobile' => 0,
            'Tablet' => 0,
            'Other' => 0,
        ];

        foreach ($stats as $stat) {
            $os = strtolower($stat->os);
            if (str_contains($os, 'windows') || str_contains($os, 'mac') || str_contains($os, 'linux') || str_contains($os, 'ubuntu')) {
                 $devices['Desktop'] += $stat->count;
            } elseif (str_contains($os, 'android') || str_contains($os, 'iphone')) {
                 $devices['Mobile'] += $stat->count;
            } elseif (str_contains($os, 'ipad') || str_contains($os, 'tablet')) {
                 $devices['Tablet'] += $stat->count;
            } else {
                 $devices['Other'] += $stat->count;
            }
        }
        
        $total = array_sum($devices);
        $result = [];
        $colors = ['#3b82f6', '#8b5cf6', '#22c55e', '#64748b']; // Blue, Purple, Green, Slate
        $i = 0;

        foreach ($devices as $name => $value) {
            if ($total > 0 && $value > 0) {
                $result[] = [
                    'name' => match($name) {
                        'Desktop' => 'الكمبيوتر',
                        'Mobile' => 'الهاتف',
                        'Tablet' => 'التابلت',
                        'Other' => 'أخرى',
                    },
                    'value' => round(($value / $total) * 100, 1),
                    'count' => $value,
                    'color' => $colors[$i++] ?? '#000000',
                ];
            }
        }
        
        // If empty, return dummy data for visual testing if needed, or empty array
        return empty($result) ? [] : $result;
    }

    /**
     * مصادر الزيارات
     */
    protected function getTrafficSources()
    {
        $stats = DB::connection('jo')->table('visitors_tracking')
            ->select('referer', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('referer')
            ->orderByDesc('count')
            ->get();

        $sources = [
            'Direct' => 0,
            'Social' => 0,
            'Search' => 0,
            'Other' => 0,
        ];

        foreach ($stats as $stat) {
            $referer = $stat->referer;
            
            if (empty($referer)) {
                $sources['Direct'] += $stat->count;
                continue;
            }

            $host = parse_url($referer, PHP_URL_HOST);
            if (!$host) $host = $referer; // Fallback
            $host = strtolower($host);

            if (str_contains($host, 'google') || str_contains($host, 'bing') || str_contains($host, 'yahoo')) {
                $sources['Search'] += $stat->count;
            } elseif (str_contains($host, 'facebook') || str_contains($host, 'twitter') || str_contains($host, 'instagram') || str_contains($host, 'linkedin') || str_contains($host, 't.co') || str_contains($host, 'youtube')) {
                $sources['Social'] += $stat->count;
            } else {
                $sources['Other'] += $stat->count;
            }
        }
        
        $result = [];
        $sourceNames = [
            'Direct' => 'مباشر',
            'Social' => 'تواصل اجتماعي',
            'Search' => 'محركات بحث',
            'Other' => 'أخرى',
        ];

        foreach ($sources as $key => $val) {
            if ($val > 0) {
                $result[] = [
                    'source' => $sourceNames[$key],
                    'visits' => $val,
                    'change' => 0, // Could calculate change compared to previous period
                ];
            }
        }

        // Always return something if empty
        if (empty($result)) {
            $result = [
                ['source' => 'مباشر', 'visits' => 0, 'change' => 0],
            ];
        }

        return $result;
    }

    /**
     * إحصائيات الزوار (مع دمج منطق الخدمة)
     */
    public function getVisitorStats(array $options = [])
    {
        // إحصائيات الزوار (من الخدمة الأصلية)
        $stats = $this->visitorService->getVisitorStats();

        // الزوار النشطين الآن بالتفصيل
        $activeVisitors = $this->getActiveVisitorsDetailed($options);

        // عدد الأعضاء النشطين الآن
        $currentMembers = Cache::remember('current_members', 300, function () {
            return User::where('last_activity', '>=', now()->subMinutes(5))->count();
        });

        // عدد الضيوف (الزوار غير المسجلين)
        $currentGuests = max(0, $stats['current'] - $currentMembers);

        // عدد الأعضاء الذين قاموا بأي نشاط اليوم
        $totalMembersToday = Cache::remember('total_members_today', 3600, function () {
            return User::where('last_activity', '>=', today())->count();
        });

        // إجمالي الزوار اليوم (زوار + أعضاء)
        $totalCombinedToday = $stats['total_today'] + $totalMembersToday;

        return [
            'current'               => $stats['current'],
            'current_members'       => $currentMembers,
            'current_guests'        => $currentGuests,
            'total_today'           => $stats['total_today'],
            'total_combined_today'  => $totalCombinedToday,
            'change'                => $stats['change'],
            'history'               => $stats['history'],
            'active_visitors'       => $activeVisitors,
        ];
    }

    /**
     * إرجاع بيانات الزوار النشطين بالتفصيل
     */
    private function getActiveVisitorsDetailed(array $options = [])
    {
        $activityWindowMinutes = (int) config('monitoring.visitor_active_minutes', 5);
        $includeBots = (bool) ($options['include_bots'] ?? true); // تضمين البوتات افتراضياً
        $withHistory = (bool) ($options['with_history'] ?? false); // تعطيل السجل افتراضياً للأداء

        $recordsQuery = DB::connection('jo')->table('visitors_tracking')
            ->where('last_activity', '>=', now()->subMinutes($activityWindowMinutes))
            ->orderBy('last_activity', 'desc');

        if (!$includeBots) {
            $recordsQuery->where(function ($query) {
                $query->whereNull('os')
                    ->orWhere('os', '!=', 'Bot');
            });
        }

        // جلب جميع الزوار النشطين بدون حد أقصى
        $records = $recordsQuery->get();

        $userIds = $records->pluck('user_id')->filter()->unique()->values();
        $users = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        $active = [];

        foreach ($records as $v) {
            $user = $v->user_id ? $users->get($v->user_id) : null;

            $page = $v->url ?? '/';
            $pageDisplay = $this->formatPageUrl($page);

            $history = collect();
            if ($withHistory) {
                // Fetch recent history for this IP
                $history = DB::connection('jo')->table('visitors_tracking')
                    ->where('ip_address', $v->ip_address)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function($h) {
                        return [
                            'url' => $h->url ?? '/',
                            'time' => $h->created_at,
                            'device' => ($h->os ?? 'Unknown') . ' - ' . ($h->browser ?? 'Unknown'),
                            'location' => ($h->country ?? 'Local') . ', ' . ($h->city ?? 'Local'),
                        ];
                    });
            }

            $active[] = [
                'ip'                => $v->ip_address,
                'country'           => $v->country ?? '??? ????',
                'city'              => $v->city ?? '??? ????',
                'browser'           => $v->browser ?? '??? ????',
                'os'                => $v->os ?? '??? ????',
                'user_agent'        => $v->user_agent ?? '',
                'current_page'      => $pageDisplay,
                'current_page_full' => $page,
                'is_member'         => $user ? true : false,
                'user_id'           => $user?->id,
                'user_name'         => $user?->name,
                'user_email'        => $user?->email,
                'user_role'         => $user?->role ?? 'User',
                'last_active'       => Carbon::parse($v->last_activity),
                'session_start'     => Carbon::parse($v->created_at ?? $v->last_activity),
                'history'           => $history,
            ];
        }

        return $active;
    }

    /**
     * تحسين صياغة الرابط للعرض
     */
    private function formatPageUrl($url)
    {
        if (!$url || $url === '/') {
            return 'الصفحة الرئيسية';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        // الصفحات الثابتة - Frontend
        $pageNames = [
            '/'                     => 'الصفحة الرئيسية',
            '/home'                 => 'الصفحة الرئيسية',
            '/login'                => 'تسجيل الدخول',
            '/register'             => 'إنشاء حساب',
            '/forgot-password'      => 'استعادة كلمة المرور',
            '/reset-password'       => 'إعادة تعيين كلمة المرور',
            '/verify-email'         => 'تأكيد البريد',
            '/news'                 => 'الأخبار',
            '/articles'             => 'المقالات',
            '/posts'                => 'المنشورات',
            '/categories'           => 'الأقسام',
            '/contact'              => 'اتصل بنا',
            '/about'                => 'من نحن',
            '/privacy'              => 'سياسة الخصوصية',
            '/terms'                => 'الشروط والأحكام',
            '/search'               => 'البحث',
            '/profile'              => 'الملف الشخصي',
            '/settings'             => 'الإعدادات',
            '/notifications'        => 'الإشعارات',
            '/messages'             => 'الرسائل',
            '/favorites'            => 'المفضلة',
            '/bookmarks'            => 'المحفوظات',
        ];

        // صفحات لوحة التحكم
        $dashboardPages = [
            '/dashboard'                    => 'لوحة التحكم',
            '/dashboard/analytics'          => 'التحليلات',
            '/dashboard/analytics/visitors' => 'تحليلات الزوار',
            '/dashboard/articles'           => 'إدارة المقالات',
            '/dashboard/articles/create'    => 'إنشاء مقال',
            '/dashboard/news'               => 'إدارة الأخبار',
            '/dashboard/news/create'        => 'إنشاء خبر',
            '/dashboard/posts'              => 'إدارة المنشورات',
            '/dashboard/posts/create'       => 'إنشاء منشور',
            '/dashboard/categories'         => 'إدارة الأقسام',
            '/dashboard/users'              => 'إدارة المستخدمين',
            '/dashboard/users/create'       => 'إنشاء مستخدم',
            '/dashboard/roles'              => 'الصلاحيات والأدوار',
            '/dashboard/settings'           => 'إعدادات النظام',
            '/dashboard/security'           => 'الأمان',
            '/dashboard/security/logs'      => 'سجلات الأمان',
            '/dashboard/sitemap'            => 'خريطة الموقع',
            '/dashboard/files'              => 'إدارة الملفات',
            '/dashboard/media'              => 'مكتبة الوسائط',
            '/dashboard/comments'           => 'إدارة التعليقات',
            '/dashboard/activities'         => 'سجل النشاطات',
            '/dashboard/notifications'      => 'الإشعارات',
            '/dashboard/messages'           => 'الرسائل',
            '/dashboard/profile'            => 'الملف الشخصي',
        ];

        // API endpoints
        $apiEndpoints = [
            '/api/front/settings'       => 'جلب إعدادات الموقع',
            '/api/front/home'           => 'جلب الصفحة الرئيسية',
            '/api/front/menu'           => 'جلب القوائم',
            '/api/front/footer'         => 'جلب الفوتر',
            '/api/categories'           => 'جلب الأقسام',
            '/api/articles'             => 'جلب المقالات',
            '/api/news'                 => 'جلب الأخبار',
            '/api/posts'                => 'جلب المنشورات',
            '/api/comments'             => 'جلب التعليقات',
            '/api/search'               => 'البحث',
            '/api/auth/login'           => 'تسجيل الدخول (API)',
            '/api/auth/register'        => 'إنشاء حساب (API)',
            '/api/auth/logout'          => 'تسجيل الخروج (API)',
            '/api/auth/user'            => 'جلب بيانات المستخدم',
            '/api/user/profile'         => 'الملف الشخصي (API)',
            '/api/user/notifications'   => 'الإشعارات (API)',
            '/api/dashboard'            => 'لوحة التحكم (API)',
            '/api/dashboard/analytics'  => 'التحليلات (API)',
            '/api/dashboard/visitor-analytics' => 'تحليلات الزوار (API)',
            '/api/dashboard/articles'   => 'المقالات (API)',
            '/api/dashboard/news'       => 'الأخبار (API)',
            '/api/dashboard/posts'      => 'المنشورات (API)',
            '/api/dashboard/users'      => 'المستخدمين (API)',
            '/api/dashboard/categories' => 'الأقسام (API)',
            '/api/dashboard/settings'   => 'الإعدادات (API)',
            '/api/dashboard/security'   => 'الأمان (API)',
            '/api/dashboard/sitemap'    => 'خريطة الموقع (API)',
        ];

        // دمج جميع الصفحات
        $allPages = array_merge($pageNames, $dashboardPages, $apiEndpoints);

        if (isset($allPages[$path])) {
            return $allPages[$path];
        }

        // التعامل مع صفحات التعديل في لوحة التحكم
        if (preg_match('/^\/dashboard\/(\w+)\/(\d+)\/edit$/', $path, $matches)) {
            $section = $matches[1];
            $id = $matches[2];
            $names = [
                'articles'   => 'تعديل مقال',
                'news'       => 'تعديل خبر',
                'posts'      => 'تعديل منشور',
                'categories' => 'تعديل قسم',
                'users'      => 'تعديل مستخدم',
                'pages'      => 'تعديل صفحة',
            ];
            if (isset($names[$section])) {
                return $names[$section] . ' #' . $id;
            }
        }

        // التعامل مع صفحات العرض في لوحة التحكم
        if (preg_match('/^\/dashboard\/(\w+)\/(\d+)$/', $path, $matches)) {
            $section = $matches[1];
            $id = $matches[2];
            $names = [
                'articles'   => 'عرض مقال',
                'news'       => 'عرض خبر',
                'posts'      => 'عرض منشور',
                'categories' => 'عرض قسم',
                'users'      => 'عرض مستخدم',
            ];
            if (isset($names[$section])) {
                return $names[$section] . ' #' . $id;
            }
        }

        // التعامل مع API endpoints بمعرف
        if (preg_match('/^\/api\/(?:dashboard\/)?(\w+)\/(\d+)/', $path, $matches)) {
            $section = $matches[1];
            $id = $matches[2];
            $names = [
                'articles'   => 'جلب مقال',
                'news'       => 'جلب خبر',
                'posts'      => 'جلب منشور',
                'categories' => 'جلب قسم',
                'users'      => 'جلب مستخدم',
                'comments'   => 'جلب تعليق',
            ];
            if (isset($names[$section])) {
                return $names[$section] . ' #' . $id;
            }
        }

        // صفحات Frontend بمعرف (مقال، خبر، منشور)
        if (preg_match('/^\/(\w+)\/([a-z0-9\-]+)$/', $path, $matches)) {
            $section = $matches[1];
            $slug = $matches[2];
            $names = [
                'articles'   => 'قراءة مقال',
                'news'       => 'قراءة خبر',
                'posts'      => 'قراءة منشور',
                'categories' => 'عرض قسم',
                'tags'       => 'عرض وسم',
                'authors'    => 'عرض كاتب',
            ];
            if (isset($names[$section])) {
                // اختصار الـ slug إذا كان طويلاً
                $shortSlug = strlen($slug) > 20 ? substr($slug, 0, 20) . '...' : $slug;
                return $names[$section];
            }
        }

        // API عام
        if (str_starts_with($path, '/api/')) {
            $apiPath = str_replace('/api/', '', $path);
            $parts = explode('/', $apiPath);
            if (count($parts) >= 1) {
                $endpoint = $parts[0];
                $endpointNames = [
                    'front'         => 'واجهة الموقع',
                    'auth'          => 'المصادقة',
                    'user'          => 'المستخدم',
                    'dashboard'     => 'لوحة التحكم',
                    'articles'      => 'المقالات',
                    'news'          => 'الأخبار',
                    'posts'         => 'المنشورات',
                    'categories'    => 'الأقسام',
                    'comments'      => 'التعليقات',
                    'search'        => 'البحث',
                    'notifications' => 'الإشعارات',
                    'settings'      => 'الإعدادات',
                    'upload'        => 'رفع ملف',
                    'media'         => 'الوسائط',
                ];
                if (isset($endpointNames[$endpoint])) {
                    return 'API: ' . $endpointNames[$endpoint];
                }
            }
            return 'API Request';
        }

        // إذا لم يتم التعرف على الصفحة
        // تنظيف المسار وعرضه
        $cleanPath = trim($path, '/');
        $cleanPath = str_replace(['/', '-', '_'], ' ', $cleanPath);
        $cleanPath = ucwords($cleanPath);

        return $cleanPath ?: $path;
    }

    /**
     * إحصائيات المستخدمين
     */
    public function getUserStats()
    {
        $total = Cache::remember('total_users', 3600, fn() => User::count());

        $active = Cache::remember('active_users', 300, fn() =>
            User::where('last_activity', '>=', now()->subMinutes(5))->count()
        );

        $newToday = Cache::remember('new_users_today', 3600, fn() =>
            User::whereDate('created_at', today())->count()
        );

        return [
            'total'     => $total,
            'active'    => $active,
            'new_today' => $newToday,
        ];
    }

    /**
     * إحصائيات الدول (آخر 7 أيام)
     */
    public function getCountryStats()
    {
        return Cache::remember('country_stats', 3600, function () {
            return DB::connection('jo')->table('visitors_tracking')
                ->select('country', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('country')
                ->where('country', '!=', 'Unknown')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'country' => $item->country,
                    'count'   => $item->count,
                ])
                ->toArray();
        });
    }

    /**
     * Parse visitor list options from request.
     */
    private function parseVisitorOptions(Request $request): array
    {
        $perPageParam = $request->input('per_page', 20);
        $includeBots = $request->boolean('include_bots', true);
        $withHistory = $request->boolean('with_history', true);

        $perPage = null;
        if ($perPageParam !== 'all' && (string) $perPageParam !== '0') {
            $perPage = (int) $perPageParam;
            if ($perPage <= 0) {
                $perPage = 20;
            }
            $perPage = max(5, min(500, $perPage));
        }

        return [
            'per_page' => $perPage,
            'include_bots' => $includeBots,
            'with_history' => $withHistory,
        ];
    }

    /**
     * GET /api/analytics/visitors
     */
    public function visitors(Request $request)
    {
        $visitorOptions = $this->parseVisitorOptions($request);
        return new BaseResource([
            'visitor_stats' => $this->getVisitorStats($visitorOptions),
            'user_stats'    => $this->getUserStats(),
            'country_stats' => $this->getCountryStats(),
        ]);
    }

    /**
     * POST /api/dashboard/visitor-analytics/prune
     */
    public function prune(Request $request)
    {
        $minutes = (int) $request->input('minutes', config('monitoring.visitor_prune_minutes', 30));
        $minutes = max(1, $minutes);
        $onlyBots = $request->boolean('only_bots', false);

        $query = VisitorTracking::query()
            ->where('last_activity', '<', now()->subMinutes($minutes));

        if ($onlyBots) {
            $query->where('os', 'Bot');
        }

        $deleted = $query->delete();

        Cache::forget('current_visitors');
        Cache::forget('total_today_visitors');
        Cache::forget('visitor_history');
        Cache::forget('country_stats');

        return new BaseResource([
            'deleted' => $deleted,
        ]);
    }

}
