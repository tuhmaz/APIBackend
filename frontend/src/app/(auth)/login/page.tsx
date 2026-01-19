"use client";

import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/components/auth/AuthProvider";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import Link from "next/link";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";

const LoginSchema = z.object({
  email: z.string().min(1, { message: "البريد الإلكتروني مطلوب" }).email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
  password: z.string().min(1, { message: "كلمة المرور مطلوبة" }).min(6, { message: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" }),
});

function LoginContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, user, loading, logout } = useAuth();

  const form = useForm<z.infer<typeof LoginSchema>>({
    resolver: zodResolver(LoginSchema),
    defaultValues: { email: "", password: "" },
    mode: "onChange",
  });

  const justRegistered = searchParams.get("registered") === "1";
  const justReset = searchParams.get("reset") === "1";
  const isSubmitting = form.formState.isSubmitting;
  const hasErrors = Object.keys(form.formState.errors).length > 0;
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(values: z.infer<typeof LoginSchema>) {
    setError(null);
    try {
      await login(values.email, values.password);
      // Only use router.replace - remove the double redirect
      router.replace("/dashboard");
    } catch (err: unknown) {
      const msg =
        err instanceof Error
          ? err.message
          : "فشل تسجيل الدخول. الرجاء التأكد من البريد الإلكتروني وكلمة المرور.";
      setError(msg);
    }
  }

  // Only logout if user is not logged in and this is initial mount
  useEffect(() => {
    if (!loading && !user) {
      try {
        void logout();
      } catch {}
    }
  }, [loading, user, logout]);

  useEffect(() => {
    if (!loading && user) {
      router.replace("/dashboard");
    }
  }, [user, loading, router]);

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <div className="w-full max-w-md rounded-xl border bg-white p-8 shadow-sm">
        <h1 className="text-2xl font-semibold mb-2 text-center">
          تسجيل الدخول
        </h1>
        <p className="text-sm text-muted-foreground text-center mb-6">
          دخول لوحة التحكم الخاصة بنظام الحوراني 2026
        </p>

        {justRegistered && (
          <Alert className="mb-4">
            <AlertDescription>
              تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.
            </AlertDescription>
            <div className="mt-2">
              <Button asChild size="sm" variant="outline">
                <Link href="/auth/verify/resend">إعادة إرسال رسالة التفعيل</Link>
              </Button>
            </div>
          </Alert>
        )}

        {justReset && (
          <Alert className="mb-4">
            <AlertDescription>
              تم تحديث كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمتك الجديدة.
            </AlertDescription>
          </Alert>
        )}

        {error && (
          <Alert variant="destructive" className="mb-4">
            <AlertDescription>{error}</AlertDescription>
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
                    <Input type="email" autoComplete="email" dir="ltr" placeholder="you@example.com" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>كلمة المرور</FormLabel>
                  <FormControl>
                    <Input type="password" autoComplete="current-password" placeholder="********" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

          <div className="flex items-center justify-between text-sm">
            <Link href="/forgot-password" className="text-primary hover:underline">
              نسيت كلمة المرور؟
            </Link>

            <Link href="/register" className="text-muted-foreground hover:underline">
              ليس لديك حساب؟ إنشاء حساب
            </Link>
          </div>

            <Button type="submit" className="w-full mt-2" disabled={isSubmitting || hasErrors}>
              {isSubmitting ? "جارٍ تسجيل الدخول..." : "تسجيل الدخول"}
            </Button>
          </form>
        </Form>
      </div>
    </main>
  );
}

export default function LoginPage() {
  return (
    <Suspense fallback={<div />}> 
      <LoginContent />
    </Suspense>
  );
}
