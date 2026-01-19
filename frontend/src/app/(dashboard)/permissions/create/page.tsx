import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import PermissionForm from "@/components/dashboard/permissions/PermissionForm";

export default function PermissionCreatePage() {
  async function createPermission(formData: FormData) {
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
    const url = `${base}${prefix}/permissions`;
    const name = String(formData.get("name") ?? "").trim();
    const guard_name = String(formData.get("guard_name") ?? "sanctum").trim() || "sanctum";
    const reqHeaders: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    if (token) reqHeaders.Authorization = `Bearer ${token}`;
    await fetch(url, { method: "POST", headers: reqHeaders, body: JSON.stringify({ name, guard_name }) });
    revalidatePath("/dashboard/permissions");
    redirect("/dashboard/permissions");
  }

  return (
    <div className="p-6">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>إنشاء صلاحية</CardTitle>
        </CardHeader>
        <CardContent>
          <PermissionForm mode="create" action={createPermission} />
          <div className="mt-4">
            <a href="/dashboard/permissions" className="text-sm underline">رجوع</a>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
