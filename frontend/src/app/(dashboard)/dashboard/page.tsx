"use client";

import { Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import { useAuth } from "@/components/auth/AuthProvider";
import { useEffect, useState } from "react";
import { apiDashboard } from "@/lib/api";

export default function DashboardHomePage() {
  return (
    <Suspense fallback={<div />}>
      <DashboardContent />
    </Suspense>
  );
}

function DashboardContent() {
  const sp = useSearchParams();
  const justVerified = sp.get("verified") === "1";
  const { user, token } = useAuth();
  const [summary, setSummary] = useState<{ posts?: number; classes?: number; users?: number } | null>(null);
  const [loading, setLoading] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    async function load() {
      if (!token) return;
      setLoading(true);
      setErr(null);
      try {
        const data = await apiDashboard(token);
        if (!cancelled) setSummary(data);
      } catch (e) {
        const m = e instanceof Error ? e.message : "فشل جلب بيانات لوحة التحكم";
        if (!cancelled) setErr(m);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    load();
    return () => {
      cancelled = true;
    };
  }, [token]);

  return (
    <div className="space-y-6">
      {justVerified && (
        <Alert className="mb-2">
          <AlertDescription>تم تأكيد بريدك الإلكتروني بنجاح.</AlertDescription>
        </Alert>
      )}

      {user && !user.email_verified_at && (
        <Alert className="mb-2">
          <AlertDescription>
            بريدك الإلكتروني غير مُفعَّل. قم بتفعيل بريدك للاستفادة من كامل الميزات.
          </AlertDescription>
          <div className="mt-2">
            <Button asChild size="sm" variant="outline">
              <Link href="/auth/verify/resend">إعادة إرسال رسالة التفعيل</Link>
            </Button>
          </div>
        </Alert>
      )}

      <Separator />

      {err && (
        <Alert className="mb-2" variant="destructive">
          <AlertDescription>{err}</AlertDescription>
        </Alert>
      )}

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle className="text-base">عدد المقالات</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{loading ? "..." : summary?.posts ?? "—"}</p>
            <p className="text-xs text-muted-foreground mt-1">لاحقًا سيتم جلبها من Laravel API</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-base">عدد الصفوف</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{loading ? "..." : summary?.classes ?? "—"}</p>
            <p className="text-xs text-muted-foreground mt-1">سيتم ربطها بـ /dashboard/school-classes</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-base">عدد المستخدمين</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{loading ? "..." : summary?.users ?? "—"}</p>
            <p className="text-xs text-muted-foreground mt-1">مستقبلاً من /dashboard/users</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
