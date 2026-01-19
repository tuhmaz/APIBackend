import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import RolesTable from "@/components/dashboard/roles/RolesTable";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

type Role = { id: number; name: string; users_count?: number | null };
type FetchResult = { roles: Role[]; error?: string };

async function fetchRoles(): Promise<FetchResult> {
  const cookieHeader = (await headers()).get("cookie") ?? "";
  const token = cookieHeader
    .split(";")
    .map((c: string) => c.trim())
    .find((c: string) => c.startsWith("token="))
    ?.split("=")[1] ?? null;
  const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
  const base = rawBase.replace(/\/+$/, "");
  const prefix = base.endsWith("/api") ? "" : "/api";
  const altBase = "http://localhost:8000/api";
  const url = `${base}${prefix}/roles`;
  const reqHeaders: Record<string, string> = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
  if (token) reqHeaders.Authorization = `Bearer ${token}`;
  try {
    const res = await fetch(url, { headers: reqHeaders, cache: "no-store", credentials: "include" });
    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const j = await res.json();
        message = String(j?.message ?? j?.data?.message ?? message);
      } catch {}
      // محاولة ثانية باستخدام localhost كـ بديل
      if (base !== altBase) {
        try {
          const altUrl = `${altBase}${altBase.endsWith("/api") ? "" : "/api"}/roles`;
          const r2 = await fetch(altUrl, { headers: reqHeaders, cache: "no-store", credentials: "include" });
          if (r2.ok) {
            const j2 = await r2.json();
            const list2 = Array.isArray(j2?.data) ? j2.data : [];
            return { roles: list2 as Role[] };
          }
        } catch {}
      }
      return { roles: [], error: message };
    }
    const json = await res.json();
    const list = Array.isArray(json?.data) ? json.data : [];
    return { roles: list as Role[] };
  } catch {
    // محاولة ثانية باستخدام localhost كـ بديل
    if (base !== altBase) {
      try {
        const altUrl = `${altBase}${altBase.endsWith("/api") ? "" : "/api"}/roles`;
        const r2 = await fetch(altUrl, { headers: reqHeaders, cache: "no-store", credentials: "include" });
        if (r2.ok) {
          const j2 = await r2.json();
          const list2 = Array.isArray(j2?.data) ? j2.data : [];
          return { roles: list2 as Role[] };
        }
      } catch {}
    }
    return { roles: [], error: "تعذر الاتصال بواجهة الـ API" };
  }
}

export default async function RolesPage() {
  const { roles, error } = await fetchRoles();

  async function deleteRole(formData: FormData) {
    "use server";
    const id = formData.get("id");
    const cookieHeader = (await headers()).get("cookie") ?? "";
    const token = cookieHeader
      .split(";")
      .map((c: string) => c.trim())
      .find((c: string) => c.startsWith("token="))
      ?.split("=")[1] ?? null;
    const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
    const base = rawBase.replace(/\/+$/, "");
    const prefix = base.endsWith("/api") ? "" : "/api";
    const url = `${base}${prefix}/roles/${id}`;
    const reqHeaders: Record<string, string> = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    await fetch(url, { method: "DELETE", headers: reqHeaders });
    revalidatePath("/dashboard/roles");
  }

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold tracking-tight">إدارة الأدوار</h1>
        <p className="text-muted-foreground mt-2">
          قم بإدارة أدوار المستخدمين والصلاحيات المرتبطة بها
        </p>
      </div>

      {error && (
        <Alert className="mb-6" variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
          <CardTitle className="text-lg font-medium">قائمة الأدوار</CardTitle>
          <Button asChild>
            <a href="/dashboard/roles/create">إنشاء دور جديد</a>
          </Button>
        </CardHeader>
        <CardContent>
          <RolesTable roles={roles} onDelete={deleteRole} />
        </CardContent>
      </Card>
    </div>
  );
}
