<?php

namespace App\Services\LegacyImport;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyImportService
{
    protected array $cfg;
    protected string $legacy = 'legacy';
    protected array $idMap = []; // ['users'=>[oldId=>newId], 'news'=>[...], ...]

    public function run(array $cfg): array
    {
        $this->cfg = $cfg;
        $this->bootLegacyConnection();

        $tables = $cfg['tables'] ?? [];
        $dry = (bool)$cfg['dry_run'];
        $truncate = (bool)$cfg['truncate_before'];
        $preserve = (bool)$cfg['preserve_ids'];

        $report = [
            'dry_run' => $dry,
            'truncate_before' => $truncate,
            'preserve_ids' => $preserve,
            'steps' => [],
        ];

        // ترتيب آمن لعلاقات المفاتيح
        $defaultPlan = [
            'users',
            'teams','team_user','team_invitations',
            'countries','school_classes','subjects','semesters',
            'categories',
            // التحويلات المهمة
            'news->posts',
            'keywords','news_keyword->post_keyword',
            'articles','article_keyword',
            'files',
            'comments','reactions',
            'conversations','messages',
            'settings',
            'visitors_tracking','page_visits','database_metrics',
            'security_logs','blocked_ips','trusted_ips','rate_limit_logs',
            'personal_access_tokens',
            'oauth_*',
        ];

        $plan = !empty($tables) ? $tables : $defaultPlan;

        if ($truncate && !$dry) {
            $this->truncateTargets($plan);
            $report['steps'][] = 'Truncated target tables.';
        }

        // تعطيل القيود أثناء الإدخال الكثيف
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($plan as $step) {
            $res = match ($step) {
                'users' => $this->migrateUsers($dry, $preserve),
                'teams' => $this->simpleCopy('teams','teams', $dry, $preserve),
                'team_user' => $this->simpleCopy('team_user','team_user', $dry, $preserve),
                'team_invitations' => $this->simpleCopy('team_invitations','team_invitations', $dry, $preserve),

                'countries' => $this->simpleCopy('countries','countries', $dry, $preserve),
                'school_classes' => $this->simpleCopy('school_classes','school_classes', $dry, $preserve),
                'subjects' => $this->simpleCopy('subjects','subjects', $dry, $preserve),
                'semesters' => $this->simpleCopy('semesters','semesters', $dry, $preserve),

                'categories' => $this->migrateCategories($dry, $preserve),

                'news->posts' => $this->migrateNewsToPosts($dry, $preserve),
                'keywords' => $this->simpleCopy('keywords','keywords', $dry, $preserve),
                'news_keyword->post_keyword' => $this->migrateNewsKeywordToPostKeyword($dry, $preserve),

                'articles' => $this->migrateArticles($dry, $preserve),
                'article_keyword' => $this->simpleCopy('article_keyword','article_keyword', $dry, $preserve),
                'files' => $this->migrateFiles($dry, $preserve),

                'comments' => $this->migrateComments($dry, $preserve),
                'reactions' => $this->migrateReactions($dry, $preserve),

                'conversations' => $this->simpleCopy('conversations','conversations', $dry, $preserve),
                'messages' => $this->simpleCopy('messages','messages', $dry, $preserve),

                'settings' => $this->upsertSettings($dry),

                'visitors_tracking' => $this->simpleCopy('visitors_tracking','visitors_tracking', $dry, $preserve),
                'page_visits' => $this->simpleCopy('page_visits','page_visits', $dry, $preserve),
                'database_metrics' => $this->simpleCopy('database_metrics','database_metrics', $dry, $preserve),

                'security_logs' => $this->simpleCopy('security_logs','security_logs', $dry, $preserve),
                'blocked_ips' => $this->simpleCopy('blocked_ips','blocked_ips', $dry, $preserve),
                'trusted_ips' => $this->simpleCopy('trusted_ips','trusted_ips', $dry, $preserve),
                'rate_limit_logs' => $this->simpleCopy('rate_limit_logs','rate_limit_logs', $dry, $preserve),

                'personal_access_tokens' => $this->simpleCopy('personal_access_tokens','personal_access_tokens', $dry, $preserve),

                'oauth_*' => $this->migrateOauth($dry, $preserve),

                default => ['step'=>$step, 'copied'=>0, 'note'=>'Skipped/Unknown'],
            };

            $report['steps'][] = $res;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // حفظ خرائط IDs للمراجعة
        if (!$dry && !empty($this->idMap)) {
            $path = storage_path('app/legacy-import-maps-'.date('Ymd_His').'.json');
            file_put_contents($path, json_encode($this->idMap, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            $report['id_maps_file'] = $path;
        }

        return $report;
    }

    protected function bootLegacyConnection(): void
    {
        config([
            'database.connections.legacy' => [
                'driver' => 'mysql',
                'host' => $this->cfg['host'],
                'port' => $this->cfg['port'],
                'database' => $this->cfg['database'],
                'username' => $this->cfg['username'],
                'password' => $this->cfg['password'] ?? null,
                'unix_socket' => env('DB_SOCKET', null),
                'charset' => $this->cfg['charset'] ?? 'utf8mb4',
                'collation' => $this->cfg['collation'] ?? 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ],
        ]);
        DB::connection('legacy')->getPdo();
    }

    protected function chunked(string $table, callable $cb, int $size = 2000): array
    {
        $conn = DB::connection($this->legacy);
        $pk = 'id';
        $max = optional($conn->table($table)->max($pk)) ?? 0;
        $copied = 0;
        $last = 0;
        while (true) {
            $rows = $conn->table($table)->where($pk, '>', $last)->orderBy($pk)->limit($size)->get();
            if ($rows->isEmpty()) break;
            $copied += $cb($rows);
            $last = $rows->last()->$pk;
        }
        return ['table'=>$table, 'copied'=>$copied];
    }

    protected function simpleCopy(string $from, string $to, bool $dry, bool $preserve): array
    {
        return $this->chunked($from, function($rows) use($to, $dry, $preserve){
            $arr = [];
            foreach ($rows as $r) {
                $row = (array)$r;
                if (!$preserve) unset($row['id']);
                $arr[] = $row;
            }
            if (!$dry && !empty($arr)) {
                DB::table($to)->insert($arr);
            }
            return count($arr);
        });
    }

    protected function truncateTargets(array $plan): void
    {
        $map = [
            'news->posts' => 'posts',
            'news_keyword->post_keyword' => 'post_keyword',
            'oauth_*' => null, // ضمن دالة خاصة
        ];
        $targets = [];
        foreach ($plan as $item) {
            if (str_contains($item, '->')) {
                $targets[] = $map[$item] ?? null;
            } else {
                $targets[] = $item;
            }
        }
        $targets = array_filter(array_unique($targets));
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($targets as $t) {
            if (DB::getSchemaBuilder()->hasTable($t)) {
                DB::table($t)->truncate();
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    // ---- جداول خاصة بفروق البنية ----

    protected function migrateUsers(bool $dry, bool $preserve): array
    {
        $copied = 0; $map = [];
        $this->chunked('users', function($rows) use(&$copied,&$map,$dry,$preserve){
            $batch = [];
            foreach ($rows as $r) {
                $row = (array)$r;
                // توافق أعمدة Laravel 12 (remember_token قد يكون موجودًا عندك فعليًا)
                $row = Arr::only($row, [
                    'id','name','email','email_verified_at','password','remember_token',
                    'current_team_id','profile_photo_path','created_at','updated_at'
                ]) + [
                    'remember_token' => $row['remember_token'] ?? null,
                ];
                if (!$preserve) unset($row['id']);
                $batch[] = $row;
            }
            if (!$dry && $batch) {
                DB::table('users')->insert($batch);
            }
            // بناء خريطة IDs
            foreach ($rows as $r) {
                $old = $r->id;
                $new = $preserve ? $old : DB::table('users')->where('email',$r->email)->value('id');
                $map[$old] = $new ?? null;
            }
            $copied += count($rows);
            return count($rows);
        });
        $this->idMap['users'] = $map;
        return ['table'=>'users', 'copied'=>$copied];
    }

    protected function migrateCategories(bool $dry, bool $preserve): array
    {
        // في الجديدة يوجد parent_id/depth/icon/image/country/slug unique
        return $this->simpleCopy('categories','categories',$dry,$preserve);
    }

    protected function migrateNewsToPosts(bool $dry, bool $preserve): array
    {
        $copied = 0; $map = [];
        $this->chunked('news', function($rows) use(&$copied,&$map,$dry,$preserve){
            $batch = [];
            foreach ($rows as $r) {
                $row = (array)$r;
                $post = [
                    'id'           => $row['id'],
                    'category_id'  => $row['category_id'],
                    'title'        => $row['title'],
                    'slug'         => $row['slug'],
                    'content'      => $row['content'],
                    'meta_description' => $row['meta_description'] ?? null,
                    'keywords'     => $row['keywords'] ?? null,
                    'image'        => $row['image'] ?? null,
                    'image_alt'    => $row['alt'] ?? null,
                    'author_id'    => $row['author_id'] ?? null, // متروك بدون FK حسب توصياتك
                    'is_active'    => $row['is_active'] ?? 1,
                    'is_featured'  => $row['is_featured'] ?? 0,
                    'views'        => $row['views'] ?? 0,
                    'country'      => $row['country'] ?? null,
                    'created_at'   => $row['created_at'] ?? now(),
                    'updated_at'   => $row['updated_at'] ?? now(),
                ];
                if (!$preserve) unset($post['id']);
                $batch[] = $post;
            }
            if (!$dry && $batch) {
                DB::table('posts')->insert($batch);
            }
            foreach ($rows as $r) {
                $old = $r->id;
                $new = $preserve ? $old : DB::table('posts')->where('slug',$r->slug)->value('id');
                $map[$old] = $new ?? null;
            }
            $copied += count($rows);
            return count($rows);
        });
        $this->idMap['news->posts'] = $map;
        return ['step'=>'news->posts', 'copied'=>$copied];
    }

    protected function migrateNewsKeywordToPostKeyword(bool $dry, bool $preserve): array
    {
        // تحويل أعمدة pivot: news_id → post_id
        $copied = 0;
        $this->chunked('news_keyword', function($rows) use(&$copied,$dry,$preserve){
            $batch = [];
            foreach ($rows as $r) {
                $row = (array)$r;
                $postId = $this->mapId('news->posts', $row['news_id']) ?? $row['news_id'];
                $pivot = [
                    'id'         => $row['id'],
                    'post_id'    => $postId,
                    'keyword_id' => $row['keyword_id'],
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ];
                if (!$preserve) unset($pivot['id']);
                $batch[] = $pivot;
            }
            if (!$dry && $batch) DB::table('post_keyword')->insert($batch);
            $copied += count($rows);
            return count($rows);
        });
        return ['step'=>'news_keyword->post_keyword', 'copied'=>$copied];
    }

    protected function migrateArticles(bool $dry, bool $preserve): array
    {
        // البنية متوافقة تقريبًا بين القديم والجديد
        return $this->simpleCopy('articles','articles',$dry,$preserve);
    }

    protected function migrateFiles(bool $dry, bool $preserve): array
    {
        // تأكد من توافق article_id (موجود) وأسماء الأعمدة
        return $this->simpleCopy('files','files',$dry,$preserve);
    }

    protected function migrateComments(bool $dry, bool $preserve): array
    {
        // تحويل commentable_type: إذا كان القديم يشير إلى "App\Models\News" فحوّله إلى "App\Models\Post"
        $copied = 0;
        $this->chunked('comments', function($rows) use(&$copied,$dry,$preserve){
            $batch = [];
            foreach ($rows as $r) {
                $row = (array)$r;
                $type = $row['commentable_type'];
                // أمثلة لأسماء قديمة:
                $type = str_replace('App\\Models\\News', 'App\\Models\\Post', $type);
                $type = str_replace('News', 'App\\Models\\Post', $type); // fallback

                if (str_contains($type, 'Post') && isset($row['commentable_id'])) {
                    $row['commentable_id'] = $this->mapId('news->posts', $row['commentable_id']) ?? $row['commentable_id'];
                }

                $data = [
                    'id' => $row['id'],
                    'body' => $row['body'],
                    'user_id' => $row['user_id'],
                    'commentable_id' => $row['commentable_id'],
                    'commentable_type' => $type,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ];
                if (!$preserve) unset($data['id']);
                $batch[] = $data;
            }
            if (!$dry && $batch) DB::table('comments')->insert($batch);
            $copied += count($rows);
            return count($rows);
        });
        return ['table'=>'comments', 'copied'=>$copied, 'note'=>'types normalized'];
    }

    protected function migrateReactions(bool $dry, bool $preserve): array
    {
        return $this->simpleCopy('reactions','reactions',$dry,$preserve);
    }

    protected function upsertSettings(bool $dry): array
    {
        $conn = DB::connection($this->legacy);
        $rows = $conn->table('settings')->get();
        $n=0;
        foreach ($rows as $r) {
            if ($dry) { $n++; continue; }
            DB::table('settings')->updateOrInsert(
                ['key'=>$r->key],
                ['value'=>$r->value, 'updated_at'=>now(), 'created_at'=>$r->created_at ?? now()]
            );
            $n++;
        }
        return ['table'=>'settings', 'upserted'=>$n];
    }

    protected function migrateOauth(bool $dry, bool $preserve): array
    {
        $copied = 0;
        foreach (['oauth_clients','oauth_access_tokens','oauth_refresh_tokens','oauth_auth_codes','oauth_personal_access_clients'] as $t) {
            if (!DB::connection($this->legacy)->getSchemaBuilder()->hasTable($t)) continue;
            $to = $t;
            // في الجديدة لديك oauth_device_codes كذلك (اختياري)
            $res = $this->simpleCopy($t, $to, $dry, $preserve);
            $copied += $res['copied'] ?? 0;
        }
        // device codes (إن وجدت في الجديدة فقط—لن تُملأ من القديمة)
        return ['group'=>'oauth_*', 'copied'=>$copied];
    }

    protected function mapId(string $scope, int|string $old): ?int
    {
        return $this->idMap[$scope][$old] ?? null;
    }
}
