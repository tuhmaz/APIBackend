import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import UsersTable from "@/components/dashboard/users/UsersTable";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";

type User = { id: number; name: string; email: string; roles?: { name: string }[] };
type FetchResult = { users: User[]; error?: string };

async function fetchUsers(): Promise<FetchResult> {
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

  const reqHeaders: Record<string, string> = { 
    Accept: "application/json", 
    "X-Requested-With": "XMLHttpRequest" 
  };
  
  if (token) reqHeaders.Authorization = `Bearer ${token}`;

  try {
    const res = await fetch(url, { 
      headers: reqHeaders, 
      cache: "no-store", 
      credentials: "include" 
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const j = await res.json();
        message = String(j?.message ?? j?.data?.message ?? message);
      } catch {}
      return { users: [], error: message };
    }

    const json = await res.json();
    const users = Array.isArray(json?.data) ? json.data : Array.isArray(json?.users) ? json.users : [];
    
    return { users: users as User[] };
  } catch (error) {
    return { 
      users: [], 
      error: error instanceof Error ? error.message : "تعذر الاتصال بالخادم" 
    };
  }
}

export default async function UsersPage() {
  const { users, error } = await fetchUsers();

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
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-6">
      <Card>
        <CardHeader>
          <CardTitle>إدارة المستخدمين</CardTitle>
        </CardHeader>
        <CardContent>
          <UsersTable users={users} onDelete={deleteUser} />
        </CardContent>
      </Card>
    </div>
  );
}

export function UsersLoading() {
  return (
    <div className="p-6 space-y-4">
      <Card>
        <CardHeader>
          <Skeleton className="h-8 w-48" />
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-12 w-full" />
            <Skeleton className="h-12 w-full" />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}