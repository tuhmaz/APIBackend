import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RoleForm from "@/components/dashboard/roles/RoleForm";

type Permission = { id: number; name: string };

async function fetchPermissions(): Promise<Permission[]> {
  const cookieHeader = (await headers()).get("cookie") ?? "";
  const token = cookieHeader
    .split(";")
    .map((c: string) => c.trim())
    .find((c: string) => c.startsWith("token="))
    ?.split("=")[1] ?? null;
  const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
  const base = rawBase.replace(/\/+$/, "");
  const prefix = base.endsWith("/api") ? "" : "/api";
  const url = `${base}${prefix}/permissions`;
  const reqHeaders: Record<string, string> = { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" };
  if (token) reqHeaders.Authorization = `Bearer ${token}`;
  const res = await fetch(url, { headers: reqHeaders, cache: "no-store" });
  const json = await res.json();
  const list = Array.isArray(json?.data) ? json.data : json?.permissions ?? [];
  return list as Permission[];
}

export default async function RoleCreatePage() {
  const permissions = await fetchPermissions();

  async function createRole(formData: FormData) {
    "use server";
    const cookieHeader = (await headers()).get("cookie") ?? "";
    const token = cookieHeader
      .split(";")
      .map((c: string) => c.trim())
      .find((c: string) => c.startsWith("token="))
      ?.split("=")[1] ?? null;
    const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
    const base = rawBase.replace(/\/+$/, "");
    const prefix = base.endsWith("/api") ? "" : "/api";
    const url = `${base}${prefix}/roles`;
    const name = String(formData.get("name") ?? "").trim();
    const permissionsIds = formData.getAll("permissions[]").map((v) => Number(v));
    
    const reqHeaders: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    
    try {
      const res = await fetch(url, { 
        method: "POST", 
        headers: reqHeaders, 
        body: JSON.stringify({ name, permissions: permissionsIds }),
        credentials: "include"
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(
          errorData.message || errorData.error || `فشل في إنشاء الدور: ${res.status}`
        );
      }

      revalidatePath("/dashboard/roles");
      redirect("/dashboard/roles");
    } catch (error) {
      console.error("Error creating role:", error);
      throw new Error(
        error instanceof Error ? error.message : "تعذر إنشاء الدور"
      );
    }
  }

  return (
    <div className="p-6">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>إنشاء دور جديد</CardTitle>
        </CardHeader>
        <CardContent>
          <RoleForm permissions={permissions} action={createRole} />
        </CardContent>
      </Card>
    </div>
  );
}
