import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Skeleton } from "@/components/ui/skeleton";
import UserDetails from "@/components/dashboard/users/UserDetails";

type User = { id: number; name: string; email: string; roles?: { name: string }[] };

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

export default async function UserViewPage({ params }: { params: Promise<{ id: string }> }) {
  const resolvedParams = await params;
  let user: User;
  let error: string | null = null;

  try {
    user = await fetchUser(resolvedParams.id);
  } catch (err) {
    error = err instanceof Error ? err.message : "تعذر تحميل بيانات المستخدم";
    user = { id: 0, name: "", email: "" };
  }

  async function deleteUser(formData: FormData) {
    "use server";
    
    const id = formData.get("id");
    if (!id) return;

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

    try {
      const res = await fetch(url, { 
        method: "DELETE", 
        headers: reqHeaders,
        credentials: "include"
      });

      if (!res.ok) {
        throw new Error(`فشل في حذف المستخدم: ${res.status}`);
      }

      revalidatePath("/dashboard/users");
      redirect("/dashboard/users");
    } catch (error) {
      console.error("Error deleting user:", error);
      throw new Error("تعذر حذف المستخدم");
    }
  }

  if (error) {
    return (
      <div className="p-6">
        <Card className="border-destructive">
          <CardHeader>
            <CardTitle className="text-destructive">خطأ في تحميل البيانات</CardTitle>
          </CardHeader>
          <CardContent>
            <Alert variant="destructive">
              <AlertDescription>
                {error}
              </AlertDescription>
            </Alert>
            <div className="mt-4">
              <a href="/dashboard/users" className="text-sm underline">
                العودة إلى قائمة المستخدمين
              </a>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-6">
      <UserDetails user={user} onDelete={deleteUser} />
    </div>
  );
}

export function UserViewLoading() {
  return (
    <div className="p-6 space-y-6">
      <div className="flex items-center justify-between">
        <div className="space-y-2">
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-4 w-64" />
        </div>
        <div className="flex gap-2">
          <Skeleton className="h-9 w-24" />
          <Skeleton className="h-9 w-24" />
          <Skeleton className="h-9 w-24" />
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-32" />
            <Skeleton className="h-4 w-48" />
          </CardHeader>
          <CardContent className="space-y-4">
            <Skeleton className="h-6 w-full" />
            <Skeleton className="h-6 w-full" />
            <Skeleton className="h-6 w-full" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <Skeleton className="h-6 w-32" />
            <Skeleton className="h-4 w-48" />
          </CardHeader>
          <CardContent>
            <Skeleton className="h-6 w-24" />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-32" />
          <Skeleton className="h-4 w-48" />
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Skeleton className="h-9 w-32" />
            <Skeleton className="h-9 w-32" />
            <Skeleton className="h-9 w-32" />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}