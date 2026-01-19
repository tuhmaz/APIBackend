import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import UserForm from "@/components/dashboard/users/UserForm";

export default function UserCreatePage() {
  async function createUser(_userId: string, formData: FormData) {
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
    const url = `${base}${prefix}/dashboard/users`;

    const name = String(formData.get("name") ?? "").trim();
    const email = String(formData.get("email") ?? "").trim();
    const password = String(formData.get("password") ?? "").trim();

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
        body: JSON.stringify({ name, email, password }),
        credentials: "include"
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(
          errorData.message || errorData.error || `فشل في إنشاء المستخدم: ${res.status}`
        );
      }

      revalidatePath("/dashboard/users");
      redirect("/dashboard/users");
    } catch (error) {
      console.error("Error creating user:", error);
      throw new Error(
        error instanceof Error ? error.message : "تعذر إنشاء المستخدم"
      );
    }
  }

  return (
    <div className="p-6">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>إنشاء مستخدم جديد</CardTitle>
        </CardHeader>
        <CardContent>
          <UserForm mode="create" action={createUser} />
        </CardContent>
      </Card>
    </div>
  );
}