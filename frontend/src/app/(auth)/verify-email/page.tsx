"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useAuth } from "@/components/auth/AuthProvider";
import { apiResendVerification } from "@/lib/api";

export default function VerifyEmailPage() {
  const router = useRouter();
  const { user, token } = useAuth();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const email = user?.email ?? "";
  const isVerified = Boolean(user?.email_verified_at);

  useEffect(() => {
    if (isVerified) {
      router.replace("/dashboard?verified=1");
    }
  }, [isVerified, router]);

  async function handleResend() {
    setError(null);
    setSuccess(null);
    setLoading(true);
    try {
      if (!token) throw new Error("يجب تسجيل الدخول لإعادة إرسال رابط التحقق");
      await apiResendVerification(token);
      setSuccess("تم إرسال رابط التحقق إلى بريدك الإلكتروني.");
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "تعذر إرسال الرابط";
      setError(msg);
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-center">تأكيد البريد الإلكتروني</CardTitle>
          <CardDescription className="text-center">
            {isVerified
              ? "تم تأكيد بريدك الإلكتروني بالفعل."
              : "لقد قمنا بإرسال رابط تأكيد البريد الإلكتروني إلى بريدك. إن لم يصلك، يمكنك إعادة الإرسال."}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {email && (
            <div className="text-sm mb-4">
              البريد المسجل: <span className="font-medium">{email}</span>
            </div>
          )}

          {error && (
            <Alert variant="destructive" className="mb-4">
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}
          {success && (
            <Alert className="mb-4">
              <AlertDescription>{success}</AlertDescription>
            </Alert>
          )}

          <Button
            onClick={handleResend}
            className="w-full"
            disabled={loading || isVerified}
          >
            {loading ? "جارٍ الإرسال..." : isVerified ? "البريد مؤكد" : "إعادة إرسال رابط التحقق"}
          </Button>
        </CardContent>
      </Card>
    </main>
  );
}
