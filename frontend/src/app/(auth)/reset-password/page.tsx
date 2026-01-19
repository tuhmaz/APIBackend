"use client";

import { Suspense, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import Link from "next/link";
import { apiResetPassword } from "@/lib/api";
import type { ApiError } from "@/lib/api";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<div />}>
      <ResetPasswordContent />
    </Suspense>
  );
}

const ResetSchema = z
  .object({
    email: z
      .string()
      .min(1, { message: "البريد الإلكتروني مطلوب" })
      .email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
    password: z
      .string()
      .min(1, { message: "كلمة المرور مطلوبة" })
      .min(6, { message: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" }),
    passwordConfirmation: z.string().min(1, { message: "تأكيد كلمة المرور مطلوب" }),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    path: ["passwordConfirmation"],
    message: "كلمتا المرور غير متطابقتين",
  });

function ResetPasswordContent() {
  const router = useRouter();
  const sp = useSearchParams();
  const initialEmail = sp.get("email") ?? "";
  const token = sp.get("token") ?? "";
  const [error, setError] = useState<string | null>(null);

  const form = useForm<z.infer<typeof ResetSchema>>({
    resolver: zodResolver(ResetSchema),
    defaultValues: {
      email: initialEmail,
      password: "",
      passwordConfirmation: "",
    },
    mode: "onChange",
  });
  const isSubmitting = form.formState.isSubmitting;
  const hasErrors = Object.keys(form.formState.errors).length > 0;

  function isApiError(err: unknown): err is ApiError {
    if (typeof err !== "object" || err === null) return false;
    const obj = err as Record<string, unknown>;
    return "status" in obj && "message" in obj;
  }

  async function onSubmit(values: z.infer<typeof ResetSchema>) {
    setError(null);
    if (!token) {
      setError("رمز إعادة التعيين غير موجود");
      return;
    }
    try {
      await apiResetPassword({
        email: values.email,
        token,
        password: values.password,
        password_confirmation: values.passwordConfirmation,
      });
      router.push("/login?reset=1");
    } catch (err: unknown) {
      const msg = isApiError(err)
        ? err.message
        : err instanceof Error
        ? err.message
        : "فشل إعادة تعيين كلمة المرور";
      setError(msg);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-slate-100">
      <Card className="w-full max-w-md">
        <CardHeader>
          <CardTitle className="text-center">إعادة تعيين كلمة المرور</CardTitle>
          <CardDescription className="text-center">أدخل كلمة مرور جديدة لحسابك</CardDescription>
        </CardHeader>
        <CardContent>
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
                    <FormLabel>كلمة المرور الجديدة</FormLabel>
                    <FormControl>
                      <Input type="password" autoComplete="new-password" placeholder="********" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="passwordConfirmation"
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
                  العودة لتسجيل الدخول
                </Link>
              </div>

              <Button type="submit" className="w-full mt-2" disabled={isSubmitting || hasErrors}>
                {isSubmitting ? "جارٍ التعيين..." : "تعيين كلمة المرور"}
              </Button>
            </form>
          </Form>
        </CardContent>
      </Card>
    </main>
  );
}
