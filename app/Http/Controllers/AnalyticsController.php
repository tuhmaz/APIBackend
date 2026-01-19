<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VisitorService;
use App\Models\User;
use App\Models\VisitorTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    protected $visitorService;

    public function __construct(VisitorService $visitorService)
    {
        $this->visitorService = $visitorService;
    }

    /**
     * عرض لوحة تحكم التحليلات
     */
    public function index()
    {
        $data = [
            'title' => 'لوحة التحليلات',
            'visitorStats' => $this->getVisitorStats(),
            'userStats' => $this->getUserStats(),
            'countryStats' => $this->getCountryStats(),
        ];

        return view('content.analytics.dashboard', $data);
    }

    /**
     * الحصول على إحصائيات الزوار
     */
    public function getVisitorStats()
    {
        // استخدام خدمة الزوار لجلب الإحصائيات
        $stats = $this->visitorService->getVisitorStats();
        
        // الحصول على الزوار النشطين مع معلومات إضافية
        $activeVisitors = $this->getActiveVisitorsDetailed();
        
        // عدد الأعضاء النشطين حالياً
        $currentMembers = Cache::remember('current_members', 300, function () {
            return User::where('last_activity', '>=', now()->subMinutes(5))->count();
        });
        
        // عدد الزوار غير المسجلين حالياً
        $currentGuests = $stats['current'] - $currentMembers;
        
        // إجمالي الزوار والأعضاء اليوم
        $totalMembersToday = Cache::remember('total_members_today', 3600, function () {
            return DB::table('users')
                ->where('last_activity', '>=', today())
                ->count();
        });
        
        $totalCombinedToday = $stats['total_today'] + $totalMembersToday;

        return [
            'current' => $stats['current'],
            'current_members' => $currentMembers,
            'current_guests' => max(0, $currentGuests),
            'total_today' => $stats['total_today'],
            'total_combined_today' => $totalCombinedToday,
            'change' => $stats['change'],
            'history' => $stats['history'],
            'active_visitors' => $activeVisitors,
        ];
    }
    
    /**
     * الحصول على الزوار النشطين مع تفاصيل إضافية
     */
    private function getActiveVisitorsDetailed()
    {
        $activeVisitors = [];
        
        // الحصول على الزوار النشطين من جدول تتبع الزوار
        $visitors = DB::table('visitors_tracking')
            ->where('last_activity', '>=', now()->subMinutes(5))
            ->orderBy('last_activity', 'desc')
            ->limit(20)
            ->get();
            
        foreach ($visitors as $visitor) {
            // التحقق من كون الزائر عضو مسجل
            $user = null;
            if ($visitor->user_id) {
                $user = User::find($visitor->user_id);
            }
            
            // تنسيق URL لعرض أفضل
            $currentPage = $visitor->url ?? '/';
            $currentPageDisplay = $this->formatPageUrl($currentPage);
            
            $activeVisitors[] = [
                'ip' => $visitor->ip_address,
                'country' => $visitor->country ?? 'غير محدد',
                'city' => $visitor->city ?? 'غير محدد',
                'browser' => $visitor->browser ?? 'غير محدد',
                'os' => $visitor->os ?? 'غير محدد',
                'current_page' => $currentPageDisplay,
                'current_page_full' => $currentPage,
                'is_member' => $user ? true : false,
                'user_name' => $user ? $user->name : null,
                'last_active' => \Carbon\Carbon::parse($visitor->last_activity),
            ];
        }
        
        return $activeVisitors;
    }
    
    /**
     * تنسيق URL للعرض بشكل أفضل
     */
    private function formatPageUrl($url)
    {
        if (empty($url) || $url === '/') {
            return 'الصفحة الرئيسية';
        }
        
        // إزالة النطاق والبروتوكول
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        
        if (empty($path)) {
            $path = '/';
        }
        
        // تحويل المسارات الشائعة إلى أسماء مفهومة
        $pageNames = [
            '/' => 'الصفحة الرئيسية',
            '/dashboard' => 'لوحة التحكم',
            '/dashboard/analytics/visitors' => 'تحليلات الزوار',
            '/login' => 'تسجيل الدخول',
            '/register' => 'التسجيل',
            '/news' => 'الأخبار',
            '/articles' => 'المقالات',
            '/contact' => 'اتصل بنا',
            '/about' => 'من نحن',
        ];
        
        // البحث عن اسم الصفحة
        if (isset($pageNames[$path])) {
            return $pageNames[$path];
        }
        
        // إذا كان المسار يحتوي على معرف
        if (preg_match('/\/(\w+)\/(\d+)/', $path, $matches)) {
            $section = $matches[1];
            $id = $matches[2];
            
            $sectionNames = [
                'news' => 'خبر رقم',
                'articles' => 'مقال رقم',
                'users' => 'مستخدم رقم',
                'categories' => 'قسم رقم',
            ];
            
            if (isset($sectionNames[$section])) {
                return $sectionNames[$section] . ' ' . $id;
            }
        }
        
        // إضافة المعاملات إذا وجدت
        $displayPath = $path;
        if ($query) {
            $displayPath .= '?' . $query;
        }
        
        return $displayPath;
    }

    /**
     * الحصول على إحصائيات المستخدمين
     */
    public function getUserStats()
    {
        // عدد المستخدمين المسجلين
        $totalUsers = Cache::remember('total_users', 3600, function () {
            return User::count();
        });

        // عدد المستخدمين النشطين (آخر 5 دقائق)
        $activeUsers = Cache::remember('active_users', 300, function () {
            return User::where('last_activity', '>=', now()->subMinutes(5))->count();
        });

        // عدد المستخدمين الجدد اليوم
        $newUsersToday = Cache::remember('new_users_today', 3600, function () {
            return User::whereDate('created_at', today())->count();
        });

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'new_today' => $newUsersToday,
        ];
    }

    /**
     * الحصول على إحصائيات الدول
     */
    public function getCountryStats()
    {
        // إحصائيات الدول (آخر 7 أيام)
        $countryStats = Cache::remember('country_stats', 3600, function () {
            return DB::table('visitors_tracking')
                ->select('country', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('country')
                ->where('country', '!=', 'Unknown')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'country' => $item->country,
                        'count' => $item->count,
                    ];
                })
                ->toArray();
        });

        return $countryStats;
    }

    /**
     * واجهة API لإحصائيات الزوار
     */
    public function visitors()
    {
        return response()->json([
            'visitor_stats' => $this->getVisitorStats(),
            'user_stats' => $this->getUserStats(),
            'country_stats' => $this->getCountryStats(),
        ]);
    }
}
