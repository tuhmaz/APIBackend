<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannedIp;
use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;

class BlockedIpsApiController extends Controller
{
    /**
     * GET /api/security/blocked-ips
     * جلب قائمة الـ IP المحظورة مع دعم البحث والفلترة
     */
    public function index(Request $request)
    {
        $query = BannedIp::with('blockedBy');

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ip', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        // الفلترة حسب الحالة
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            }
        }

        $blockedIps = $query->latest('created_at')
            ->paginate($request->per_page ?? 15);

        return new BaseResource([
            'data' => $blockedIps
        ]);
    }

    /**
     * POST /api/security/blocked-ips
     * حظر عنوان IP يدوياً
     */
    public function store(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip|unique:banned_ips,ip',
            'reason' => 'nullable|string|max:255',
            'days' => 'nullable|integer|min:0' // 0 = مؤبد (أو حسب المنطق)
        ]);

        $ip = BannedIp::ban(
            $request->ip,
            $request->reason ?? 'Manual Block',
            $request->days ?? null, // null = دائم إذا لم يحدد أيام
            auth()->id()
        );

        return new BaseResource([
            'message' => 'تم حظر العنوان بنجاح',
            'data' => $ip
        ]);
    }

    /**
     * DELETE /api/security/blocked-ips/{id}
     * حذف IP واحد من قائمة الحظر
     */
    public function destroy($id)
    {
        $ip = BannedIp::find($id);

        if (!$ip) {
            return (new BaseResource(['message' => 'IP غير موجود']))
                ->response(request())
                ->setStatusCode(404);
        }

        $ip->delete();

        return new BaseResource([
            'message' => 'تم إزالة IP من قائمة الحظر'
        ]);
    }

    /**
     * DELETE /api/security/blocked-ips/bulk
     * حذف مجموعة من الـ IPs عبر IDs
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:banned_ips,id'
        ]);

        BannedIp::whereIn('id', $request->ids)->delete();

        return new BaseResource([
            'message' => 'تم إزالة عناوين IP المحددة من قائمة الحظر'
        ]);
    }
}
