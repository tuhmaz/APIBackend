<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarkUserOnline
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = Auth::user();
            if ($user) {
                $updates = [];
                $now = now();

                if (Schema::hasColumn('users', 'is_online')) {
                    $updates['is_online'] = true;
                }
                if (Schema::hasColumn('users', 'last_seen_at')) {
                    $updates['last_seen_at'] = $now;
                }
                if (Schema::hasColumn('users', 'last_activity')) {
                    $updates['last_activity'] = $now;
                }

                if (!empty($updates)) {
                    DB::table('users')->where('id', $user->id)->update($updates);
                }

                // Optional: update visitor_sessions last_activity for this user if table exists
                if (Schema::hasTable('visitor_sessions')) {
                    try {
                        DB::table('visitor_sessions')
                            ->updateOrInsert(
                                ['user_id' => $user->id],
                                [
                                    'last_activity' => $now,
                                    'is_bot' => false,
                                    'is_mobile' => $request->userAgent() ? preg_match('/Mobile|Android|iP(hone|od|ad)/i', $request->userAgent()) > 0 : false,
                                    'is_desktop' => true,
                                    'country' => null,
                                    'url' => $request->fullUrl(),
                                    'updated_at' => $now,
                                    'created_at' => DB::raw("COALESCE(created_at, '$now')"),
                                ]
                            );
                    } catch (\Throwable $e) {
                        // Silent fail to avoid breaking request
                    }
                }
            }
        } catch (\Throwable $e) {
            // Do not block the request on presence update failure
        }

        return $response;
    }
}
