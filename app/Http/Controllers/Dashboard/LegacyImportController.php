<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\LegacyImportRequest;
use App\Services\LegacyImport\LegacyImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LegacyImportController extends Controller
{
    public function create()
    {
        return view('content.dashboard.legacy-import.create');
    }

    public function test(LegacyImportRequest $request)
    {
        $cfg = $request->validated();
        // إنشاء اتصال مؤقت باسم legacy
        config([
            'database.connections.legacy' => [
                'driver' => 'mysql',
                'host' => $cfg['host'],
                'port' => $cfg['port'],
                'database' => $cfg['database'],
                'username' => $cfg['username'],
                'password' => $cfg['password'],
                'unix_socket' => env('DB_SOCKET', null),
                'charset' => $cfg['charset'],
                'collation' => $cfg['collation'],
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ],
        ]);

        try {
            DB::connection('legacy')->getPdo(); // اختبار
            $tables = DB::connection('legacy')->select('SHOW TABLES');
            return response()->json([
                'ok' => true,
                'message' => 'تم الاتصال بنجاح',
                'tables' => array_map('current', $tables),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok'=>false, 'error'=>$e->getMessage()], 500);
        }
    }

    public function run(LegacyImportRequest $request, LegacyImportService $service)
    {
        $cfg = $request->validated();
        try {
            $report = $service->run($cfg);
            return response()->json(['ok'=>true, 'report'=>$report]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok'=>false, 'error'=>$e->getMessage()], 500);
        }
    }
}
