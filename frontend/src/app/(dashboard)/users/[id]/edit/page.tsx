import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import UserForm from "@/components/dashboard/users/UserForm";

type User = { id: number; name: string; email: string };

async function fetchUser(id: string): Promise<User> {
  const cookieHeader = (await headers()).get("cookie") ?? "";
  const token = cookieHeader
    .split(";")
    .map((c: string) => c.trim())
    .find((c: string) => c.startsWith("token="))
    ?.split("=")[1] ?? null;

  const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
  const base = rawBase.replace(/\/+$/, "");
  const prefix = base.endsWith("/api") ? "" : "/api";
  const url = `${base}${prefix}/dashboard/users/${id}`;

  const reqHeaders: Record<string, string> = { 
    Accept: "application/json", 
    "X-Requested-With": "XMLHttpRequest" 
  };
  
  if (token) reqHeaders.Authorization = `Bearer ${token}`;

  const res = await fetch(url, { 
    headers: reqHeaders, 
    cache: "no-store",
    credentials: "include"
  });

  if (!res.ok) {
    const errorData = await res.json().catch(() => ({}));
    throw new Error(
      errorData.message || errorData.error || `فشل في تحميل المستخدم: ${res.status}`
    );
  }

  const json = await res.json();
  const user = (json?.data ?? json?.user ?? json) as User;
  
  return user;
}

export default async function UserEditPage({ params }: { params: Promise<{ id: string }> }) {
  const resolvedParams = await params;
  const userId = resolvedParams.id;
  const user = await fetchUser(userId);

  async function updateUser(uid: string, formData: FormData) {
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
    const url = `${base}${prefix}/dashboard/users/${uid}`;

    const name = String(formData.get("name") ?? "").trim();
    const email = String(formData.get("email") ?? "").trim();
    const password = String(formData.get("password") ?? "").trim();

    const updateData: { name: string; email: string; password?: string } = { name, email };
    if (password) {
      updateData.password = password;
    }

    const reqHeaders: Record<string, string> = {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
    
    if (token) reqHeaders.Authorization = `Bearer ${token}`;

    try {
      const res = await fetch(url, {
        method: "PUT",
        headers: reqHeaders,
        body: JSON.stringify(updateData),
        credentials: "include"
      });

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({}));
        throw new Error(
          errorData.message || errorData.error || `فشل في تحديث المستخدم: ${res.status}`
        );
      }

      revalidatePath("/dashboard/users");
      redirect("/dashboard/users");
    } catch (error) {
      console.error("Error updating user:", error);
      throw new Error(
        error instanceof Error ? error.message : "تعذر تحديث المستخدم"
      );
    }
  }

  return (
    <div className="p-6">
      <Card className="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>تعديل المستخدم</CardTitle>
        </CardHeader>
        <CardContent>
          <UserForm
            mode="edit"
            initial={{ name: user.name, email: user.email }}
            action={updateUser}
            userId={userId}
          />
        </CardContent>
      </Card>
    </div>
  );
}