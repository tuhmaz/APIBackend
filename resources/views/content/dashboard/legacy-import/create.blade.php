@extends('layouts/layoutMaster')
@section('title', 'Legacy DB Import')

@section('content')
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">استيراد قاعدة بيانات قديمة (Laravel 11 → 12)</h5>
    </div>
    <div class="card-body">
      <form id="legacyImportForm" method="POST" action="{{ route('dashboard.legacy-import.run') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Host</label>
            <input name="host" class="form-control" required placeholder="127.0.0.1">
          </div>
          <div class="col-md-2">
            <label class="form-label">Port</label>
            <input name="port" class="form-control" value="3306">
          </div>
          <div class="col-md-3">
            <label class="form-label">Database</label>
            <input name="database" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">Charset</label>
            <input name="charset" class="form-control" value="utf8mb4">
          </div>
          <div class="col-md-3">
            <label class="form-label">Collation</label>
            <input name="collation" class="form-control" value="utf8mb4_unicode_ci">
          </div>

          <div class="col-12">
            <hr>
          </div>

          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="dry_run" id="dry_run" checked>
              <label class="form-check-label" for="dry_run">تشغيل تجريبي (لا يكتب)</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="truncate_before" id="truncate_before">
              <label class="form-check-label" for="truncate_before">تفريغ الجداول المستهدفة قبل النقل</label>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="preserve_ids" id="preserve_ids" checked>
              <label class="form-check-label" for="preserve_ids">الإبقاء على المعرفات الأصلية (IDs)</label>
            </div>
          </div>

          <div class="col-md-12">
            <label class="form-label">الجداول المراد نقلها (اتركه فارغًا = الكل)</label>
            <select name="tables[]" class="form-select" multiple size="14">
              {{-- قائمة ثابتة تعمل بشكل ممتاز --}}
              <option value="users">users</option>
              <option value="roles">roles</option>
              <option value="permissions">permissions</option>
              <option value="model_has_roles">model_has_roles</option>
              <option value="model_has_permissions">model_has_permissions</option>
              <option value="role_has_permissions">role_has_permissions</option>
              <option value="settings">settings</option>
              <option value="articles">articles</option>
              <option value="files">files</option>
              <option value="keywords">keywords</option>
              <option value="subjects">subjects</option>
				<option value="school_classes">school_classes</option>
				
              <option value="semesters">semesters</option>
              <option value="categories">categories</option>
              <option value="comments">comments</option>
              <option value="reactions">reactions</option>
              <option value="conversations">conversations</option>
              <option value="messages">messages</option>
              <option value="news">news → posts</option>
              <option value="news_keyword">news_keyword → post_keyword</option>
              <option value="article_keyword">article_keyword</option>
              <option value="page_visits">page_visits</option>
              <option value="activity_log">activity_log</option>
              <option value="notifications">notifications</option>
              <option value="telescope_entries">telescope_entries</option>
              <option value="telescope_entries_tags">telescope_entries_tags</option>
              <option value="visitors_tracking">visitors_tracking</option>
              <option value="security_logs">security_logs</option>
              <option value="rate_limit_logs">rate_limit_logs</option>
              <option value="password_reset_tokens">password_reset_tokens</option>
              <option value="migrations">migrations</option>
              <option value="jobs">jobs</option>
              <option value="blocked_ips">blocked_ips</option>
              <option value="trusted_ips">trusted_ips</option>
              <option value="database_metrics">database_metrics</option>
              <option value="countries">countries</option>
              <option value="teams">teams</option>
              <option value="team_user">team_user</option>
              <option value="team_invitations">team_invitations</option>
              <option value="failed_jobs">failed_jobs</option>
              <option value="job_batches">job_batches</option>
              <option value="personal_access_tokens">personal_access_tokens</option>
              <option value="oauth_clients">oauth_clients</option>
              <option value="oauth_access_tokens">oauth_access_tokens</option>
              <option value="oauth_refresh_tokens">oauth_refresh_tokens</option>
              <option value="oauth_auth_codes">oauth_auth_codes</option>
              <option value="oauth_personal_access_clients">oauth_personal_access_clients</option>
              <option value="sessions">sessions</option>
              <option value="cache">cache</option>
              <option value="cache_locks">cache_locks</option>
              <option value="cache_performance_logs">cache_performance_logs</option>
              <option value="redis_logs">redis_logs</option>
              <option value="events">events</option>
              <option value="sitemap_exclusions">sitemap_exclusions</option>
              <option value="telescope_monitoring">telescope_monitoring</option>
              {{-- أضف ما تحتاجه لاحقًا --}}
            </select>
            <small class="text-muted d-block mt-1">ستُطبّق التحويلات الآتية تلقائيًا عند التنفيذ: news→posts،
              news_keyword→post_keyword…</small>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="button" id="btnTest" class="btn btn-secondary">اختبار الاتصال</button>
            <button type="submit" class="btn btn-primary">بدء النقل</button>
          </div>
        </div>
      </form>

      <div class="mt-4" id="resultBox" style="display:none">
        <pre class="border p-3 bg-light" id="resultText"></pre>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      document.getElementById('btnTest').addEventListener('click', async function() {
        const form = document.getElementById('legacyImportForm');
        const data = new FormData(form);
        const res = await fetch('{{ route('dashboard.legacy-import.test') }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: data
        });
        const json = await res.json();
        const box = document.getElementById('resultBox');
        const txt = document.getElementById('resultText');
        box.style.display = 'block';
        txt.textContent = JSON.stringify(json, null, 2);
      });

      document.getElementById('legacyImportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);
        const res = await fetch('{{ route('dashboard.legacy-import.run') }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: data
        });
        const json = await res.json();
        const box = document.getElementById('resultBox');
        const txt = document.getElementById('resultText');
        box.style.display = 'block';
        txt.textContent = JSON.stringify(json, null, 2);
      });
    </script>
  @endpush
@endsection
