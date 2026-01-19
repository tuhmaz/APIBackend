import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RoleForm from "@/components/dashboard/roles/RoleForm";

type Permission = { id: number; name: string };
type Role = { id: number; name: string; permissions?: Permission[] };

async function fetchRole(id: string): Promise<Role> {
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
  const res = await fetch(url, { headers: reqHeaders, cache: "no-store" });
  const json = await res.json();
  const role = (json?.data ?? json) as Role;
  return role;
}

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

export default async function RoleEditPage({ params }: { params: { id: string } }) {
  const role = await fetchRole(params.id);
  const permissions = await fetchPermissions();
  const selected = (role.permissions ?? []).map((p) => p.id);

  async function updateRole(formData: FormData) {
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
    const url = `${base}${prefix}/roles/${params.id}`;
    const name = String(formData.get("name") ?? "");
    const permissionsIds = formData.getAll("permissions[]").map((v) => Number(v));
    const reqHeaders: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    await fetch(url, { method: "PUT", headers: reqHeaders, body: JSON.stringify({ name, permissions: permissionsIds }) });
    revalidatePath("/dashboard/roles");
    redirect("/dashboard/roles");
  }

  return (
    <div className="p-6">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>تعديل دور</CardTitle>
        </CardHeader>
        <CardContent>
          <RoleForm initial={{ name: role.name, permissionIds: selected }} permissions={permissions} action={updateRole} />
        </CardContent>
      </Card>
    </div>
  );
}
