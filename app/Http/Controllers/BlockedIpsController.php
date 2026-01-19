<?php

namespace App\Http\Controllers;

use App\Models\BannedIp;
use Illuminate\Http\Request;

class BlockedIpsController extends Controller
{
    public function index()
    {
        $blockedIps = BannedIp::with('blockedBy')
            ->latest('created_at')
            ->paginate(15);

        return view('dashboard.security.blocked-ips.index', compact('blockedIps'));
    }

    public function destroy(BannedIp $blockedIp)
    {
        $blockedIp->delete();
        return redirect()->route('security.blocked-ips.index')
            ->with('success', 'تم إزالة IP من قائمة الحظر بنجاح');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:banned_ips,id'
        ]);

        BannedIp::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة عناوين IP المحددة من قائمة الحظر بنجاح'
        ]);
    }
}
