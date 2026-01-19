"use client";

import { Suspense, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Alert, AlertDescription } from "@/components/ui/alert";
import Link from "next/link";
import { useAuth } from "@/components/auth/AuthProvider";
import type { ApiError } from "@/lib/api";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";

export default function RegisterPage() {
  return (
    <Suspense fallback={<div />}>
      <RegisterContent />
    </Suspense>
  );
}

const RegisterSchema = z
  .object({
    name: z
      .string()
      .min(1, { message: "الاسم مطلوب" })
      .min(2, { message: "الاسم يجب أن يكون حرفين على الأقل" }),
    email: z
      .string()
      .min(1, { message: "البريد الإلكتروني مطلوب" })
      .email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
    password: z
      .string()
      .min(1, { message: "كلمة المرور مطلوبة" })
      .min(6, { message: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" }),
    password_confirmation: z.string().min(1, { message: "تأكيد كلمة المرور مطلوب" }),
  })
  .refine((data) => data.password === data.password_confirmation, {
    path: ["password_confirmation"],
    message: "كلمتا المرور غير متطابقتين",
  });

function RegisterContent() {
  const router = useRouter();
  const sp = useSearchParams();
  const fromLogin = sp.get("from") === "login";
  const { register } = useAuth();
  const [error, setError] = useState<string | null>(null);

  const form = useForm<z.infer<typeof RegisterSchema>>({
    resolver: zodResolver(RegisterSchema),
    defaultValues: {
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
    },
    mode: "onChange",
  });
  const isSubmitting = form.formState.isSubmitting;
  const hasErrors = Object.keys(form.formState.errors).length > 0;

  function isApiError(e: unknown): e is ApiError {
    if (typeof e !== "object" || e === null) return false;
    const obj = e as Record<string, unknown>;
    return "status" in obj && "message" in obj;
  }

  async function onSubmit(values: z.infer<typeof RegisterSchema>) {
    setError(null);
    try {
      await register(values.name, values.email, values.password, values.password_confirmation);
      router.push("/login?registered=1");
    } catch (err: unknown) {
      const msg = isApiError(err)
        ? err.message
        : err instanceof Error
        ? err.message
        : "فشل إنشاء الحساب";
      setError(msg);
      if (isApiError(err) && err.errors) {
        const errs = err.errors;
        if (errs.name?.[0]) form.setError("name", { message: errs.name[0] });
        if (errs.email?.[0]) form.setError("email", { message: errs.email[0] });
        if (errs.password?.[0]) form.setError("password", { message: errs.password[0] });
        if (errs.password_confirmation?.[0]) form.setError("password_confirmation", { message: errs.password_confirmation[0] });
      }
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-center">إنشاء حساب</CardTitle>
          <CardDescription className="text-center">
            سجل حسابك للوصول إلى لوحة التحكم
          </CardDescription>
        </CardHeader>
        <CardContent>
          {fromLogin && !error && (
            <Alert className="mb-4">
              <AlertDescription>سجّل حسابك للمتابعة إلى لوحة التحكم.</AlertDescription>
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
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>الاسم الكامل</FormLabel>
                    <FormControl>
                      <Input autoComplete="name" placeholder="الاسم الكامل" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

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

              <FormField
                control={form.control}
                name="password"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>كلمة المرور</FormLabel>
                    <FormControl>
                      <Input type="password" autoComplete="new-password" placeholder="********" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="password_confirmation"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>تأكيد كلمة المرور</FormLabel>
                    <FormControl>
                      <Input type="password" autoComplete="new-password" placeholder="********" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className="flex items-center justify-between text-sm">
                <Link href="/login" className="text-primary hover:underline">
                  لديك حساب؟ تسجيل الدخول
                </Link>
              </div>

              <Button type="submit" className="w-full mt-2" disabled={isSubmitting || hasErrors}>
                {isSubmitting ? "جارٍ إنشاء الحساب..." : "تسجيل"}
              </Button>
            </form>
          </Form>
        </CardContent>
      </Card>
    </main>
  );
}
