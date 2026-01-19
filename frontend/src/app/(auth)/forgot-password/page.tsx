"use client";

import { useState, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import Link from "next/link";
import { apiForgotPassword } from "@/lib/api";
import type { ApiError } from "@/lib/api";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";

export default function ForgotPasswordPage() {
  return (
    <Suspense fallback={<div />}>
      <ForgotPasswordContent />
    </Suspense>
  );
}

const ForgotSchema = z.object({
  email: z.string().min(1, { message: "البريد الإلكتروني مطلوب" }).email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
});

function ForgotPasswordContent() {
  const sp = useSearchParams();
  const fromLogin = sp.get("from") === "login";
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const form = useForm<z.infer<typeof ForgotSchema>>({
    resolver: zodResolver(ForgotSchema),
    defaultValues: { email: "" },
    mode: "onChange",
  });
  const isSubmitting = form.formState.isSubmitting;
  const hasErrors = Object.keys(form.formState.errors).length > 0;

  function isApiError(err: unknown): err is ApiError {
    if (typeof err !== "object" || err === null) return false;
    const obj = err as Record<string, unknown>;
    return "status" in obj && "message" in obj;
  }

  async function onSubmit(values: z.infer<typeof ForgotSchema>) {
    setError(null);
    setSuccess(null);
    try {
      await apiForgotPassword(values.email);
      setSuccess("تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.");
    } catch (err: unknown) {
      const msg = isApiError(err)
        ? err.message
        : err instanceof Error
        ? err.message
        : "فشل إرسال الرابط";
      setError(msg);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-center">استعادة كلمة المرور</CardTitle>
          <CardDescription className="text-center">أدخل بريدك الإلكتروني لإرسال رابط إعادة التعيين</CardDescription>
        </CardHeader>
        <CardContent>
          {fromLogin && !success && !error && (
            <Alert className="mb-4">
              <AlertDescription>أدخل بريدك لإرسال رابط الاستعادة.</AlertDescription>
            </Alert>
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

          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>البريد الإلكتروني</FormLabel>
                    <FormControl>
                      <Input type="email" dir="ltr" autoComplete="email" placeholder="you@example.com" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="flex items-center justify-between text-sm">
                <Link href="/login" className="text-primary hover:underline">
                  العودة لتسجيل الدخول
                </Link>
              </div>

              <Button type="submit" className="w-full mt-2" disabled={isSubmitting || hasErrors}>
                {isSubmitting ? "جارٍ الإرسال..." : "إرسال رابط"}
              </Button>
            </form>
          </Form>
        </CardContent>
      </Card>
    </main>
  );
}
