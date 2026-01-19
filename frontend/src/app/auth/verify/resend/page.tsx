"use client";

import { useState } from "react";
import { useAuth } from "@/components/auth/AuthProvider";
import { apiResendVerification } from "@/lib/api";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";

export default function ResendVerifyPage() {
  const { token, user } = useAuth();
  const [msg, setMsg] = useState<string>("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleResend() {
    setError(null);
    setMsg("");
    setLoading(true);
    try {
      if (!token) throw new Error("يجب تسجيل الدخول لإعادة إرسال رسالة التفعيل");
      await apiResendVerification(token);
      setMsg("تم إرسال رسالة التفعيل إلى بريدك");
    } catch (e) {
      const message = e instanceof Error ? e.message : "فشل الإرسال";
      setError(message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-center">إعادة إرسال تفعيل البريد</CardTitle>
          <CardDescription className="text-center">
            {user?.email ? (
              <span>
                سيتم الإرسال إلى: <span className="font-medium">{user.email}</span>
              </span>
            ) : (
              "سجّل الدخول لإعادة إرسال رسالة التفعيل"
            )}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {error && (
            <Alert variant="destructive" className="mb-4">
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}
          {msg && (
            <Alert className="mb-4">
              <AlertDescription>{msg}</AlertDescription>
            </Alert>
          )}
          <Button onClick={handleResend} className="w-full" disabled={loading || !token}>
            {loading ? "جارٍ الإرسال..." : "إعادة إرسال"}
          </Button>
        </CardContent>
      </Card>
    </main>
  );
}
