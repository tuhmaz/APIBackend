<?php

namespace App\Http\Controllers\Dashboard\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\BannedIp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BanController extends Controller
{
    // عرض جميع الآيبيات المحظورة
    public function index(Request $request)
    {
        // الإحصائيات (بناءً على الموديل BannedIp)
        $stats = [
            'total'     => \App\Models\BannedIp::count(),
            'active'    => \App\Models\BannedIp::active()->count(),
            'expired'   => \App\Models\BannedIp::expired()->count(),
            'permanent' => \App\Models\BannedIp::whereNull('banned_until')->count(),
        ];

        // الفلترة حسب الحالة (اختياري)
        $query = \App\Models\BannedIp::query();
        if ($request->status === 'active') {
            $query->active();
        } elseif ($request->status === 'expired') {
            $query->expired();
        } elseif ($request->status === 'permanent') {
            $query->whereNull('banned_until');
        }
        $bans = $query->latest()->paginate(20);

        return view('content.dashboard.monitoring.bans', compact('bans', 'stats'));
    }


    // نموذج إضافة حظر جديد
    public function create()
    {
        return view('dashboard.monitoring.bans.create');
    }

    // إضافة آيبي جديد للحظر
    public function store(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip|unique:banned_ips,ip',
            'reason' => 'nullable|string|max:255',
            'banned_until' => 'nullable|date|after:now',
        ]);

        BannedIp::create([
            'ip' => $request->ip,
            'reason' => $request->reason,
            'banned_by' => Auth::id(),
            'banned_until' => $request->banned_until,
        ]);

        return redirect()->route('dashboard.monitoring.bans.index')->with('success', 'IP banned successfully.');
    }

    // رفع الحظر عن آيبي
    public function destroy($id)
    {
        $ban = BannedIp::findOrFail($id);
        $ban->delete();

        return redirect()->route('dashboard.monitoring.bans.index')->with('success', 'Ban removed successfully.');
    }

    // إظهار تفاصيل الحظر
    public function show($id)
    {
        $ban = BannedIp::with('admin')->findOrFail($id);
        return view('dashboard.monitoring.bans.show', compact('ban'));
    }

    // تعديل الحظر (اختياري)
    public function edit($id)
    {
        $ban = BannedIp::findOrFail($id);
        return view('dashboard.monitoring.bans.edit', compact('ban'));
    }

    public function update(Request $request, $id)
    {
        $ban = BannedIp::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string|max:255',
            'banned_until' => 'nullable|date|after:now',
        ]);

        $ban->update([
            'reason' => $request->reason,
            'banned_until' => $request->banned_until,
        ]);

        return redirect()->route('dashboard.monitoring.bans.index')->with('success', 'Ban updated successfully.');
    }
}
