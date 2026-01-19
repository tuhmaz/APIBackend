import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import PermissionForm from "@/components/dashboard/permissions/PermissionForm";

type Permission = { id: number; name: string; guard_name?: string };

async function fetchPermission(id: string): Promise<Permission> {
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
  const res = await fetch(url, { headers: reqHeaders, cache: "no-store" });
  const json = await res.json();
  const p = (json?.data ?? json?.permission ?? json) as Permission;
  return p;
}

export default async function PermissionEditPage({ params }: { params: { id: string } }) {
  const perm = await fetchPermission(params.id);

  async function updatePermission(formData: FormData) {
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
    const url = `${base}${prefix}/permissions/${params.id}`;
    const name = String(formData.get("name") ?? "").trim();
    const guard_name = String(formData.get("guard_name") ?? "").trim() || "sanctum";
    const reqHeaders: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    await fetch(url, { method: "PUT", headers: reqHeaders, body: JSON.stringify({ name, guard_name }) });
    revalidatePath("/dashboard/permissions");
    redirect("/dashboard/permissions");
  }

  return (
    <div className="p-4 max-w-md">
      <h1 className="text-xl font-semibold mb-4">تعديل صلاحية</h1>
      <PermissionForm mode="edit" initial={{ name: perm.name, guard_name: perm.guard_name ?? "sanctum" }} action={updatePermission} />
      <div className="mt-2">
        <a href="/dashboard/permissions" className="text-sm underline">إلغاء</a>
      </div>
    </div>
  );
}
