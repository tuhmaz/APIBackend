import { headers } from "next/headers";
import PermissionsTable from "@/components/dashboard/permissions/PermissionsTable";
import { revalidatePath } from "next/cache";
import { Alert, AlertDescription } from "@/components/ui/alert";

type Permission = { id: number; name: string; guard_name?: string };
type FetchResult = { permissions: Permission[]; error?: string };

async function fetchPermissions(): Promise<FetchResult> {
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
  const url = `${base}${prefix}/permissions`;
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
          const altUrl = `${altBase}${altBase.endsWith("/api") ? "" : "/api"}/permissions`;
          const r2 = await fetch(altUrl, { headers: reqHeaders, cache: "no-store", credentials: "include" });
          if (r2.ok) {
            const j2 = await r2.json();
            const list2 = Array.isArray(j2?.data)
              ? j2.data
              : Array.isArray(j2?.permissions)
              ? j2.permissions
              : Array.isArray(j2?.data?.permissions)
              ? j2.data.permissions
              : [];
            return { permissions: list2 as Permission[] };
          }
        } catch {}
      }
      return { permissions: [], error: message };
    }
    const json = await res.json();
    const list = Array.isArray(json?.data)
      ? json.data
      : Array.isArray(json?.permissions)
      ? json.permissions
      : Array.isArray(json?.data?.permissions)
      ? json.data.permissions
      : [];
    return { permissions: list as Permission[] };
  } catch {
    // محاولة ثانية باستخدام localhost كـ بديل
    if (base !== altBase) {
      try {
        const altUrl = `${altBase}${altBase.endsWith("/api") ? "" : "/api"}/permissions`;
        const r2 = await fetch(altUrl, { headers: reqHeaders, cache: "no-store", credentials: "include" });
        if (r2.ok) {
          const j2 = await r2.json();
          const list2 = Array.isArray(j2?.data)
            ? j2.data
            : Array.isArray(j2?.permissions)
            ? j2.permissions
            : Array.isArray(j2?.data?.permissions)
            ? j2.data.permissions
            : [];
          return { permissions: list2 as Permission[] };
        }
      } catch {}
    }
    return { permissions: [], error: "تعذر الاتصال بواجهة الـ API" };
  }
}

export default async function PermissionsPage() {
  const { permissions, error } = await fetchPermissions();
  async function deletePermission(formData: FormData) {
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
    const url = `${base}${prefix}/permissions/${id}`;
    const reqHeaders: Record<string, string> = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    await fetch(url, { method: "DELETE", headers: reqHeaders });
    revalidatePath("/dashboard/permissions");
  }
  return (
    <div className="p-4">
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-semibold">الصلاحيات</h1>
        <a href="/dashboard/permissions/create" className="text-sm underline">إنشاء صلاحية</a>
      </div>
      {error && (
        <Alert className="mb-4" variant="destructive">
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}
      <PermissionsTable permissions={permissions} onDelete={deletePermission} />
    </div>
  );
}
