<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrustedIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\TrustedIpResource;
use App\Http\Resources\BaseResource;

class TrustedIpApiController extends Controller
{
    /**
     * عرض قائمة الـ Trusted IPs
     */
    public function index()
    {
        $ips = TrustedIp::with('addedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return TrustedIpResource::collection($ips)
            ->additional(['success' => true]);
    }

    /**
     * إضافة IP موثوق
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip'
        ]);

        // منع التكرار
        if (TrustedIp::where('ip_address', $validated['ip_address'])->exists()) {
            return (new BaseResource(['message' => 'هذا الـ IP موجود بالفعل في القائمة الموثوقة']))
                ->response($request)
                ->setStatusCode(409);
        }

        $trusted = TrustedIp::create([
            'ip_address' => $validated['ip_address'],
            'added_by' => Auth::id()
        ]);

        return (new TrustedIpResource($trusted->load('addedBy')))
            ->additional(['message' => 'تم إضافة الـ IP إلى القائمة الموثوقة'])
            ->response($request)
            ->setStatusCode(201);
    }

    /**
     * تحقق هل IP موثوق
     */
    public function check(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $exists = TrustedIp::where('ip_address', $request->ip)->exists();

        return new BaseResource(['trusted' => $exists]);
    }

    /**
     * حذف Trusted IP
     */
    public function destroy(TrustedIp $trustedIp)
    {
        $trustedIp->delete();

        return new BaseResource(['message' => 'تم حذف الـ IP من القائمة الموثوقة']);
    }
}
