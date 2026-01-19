"use client";

import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

const CreateSchema = z.object({
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  email: z.string().min(1, { message: "البريد الإلكتروني مطلوب" }).email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
  password: z.string().min(6, { message: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" }),
});

const EditSchema = z.object({
  name: z.string().min(1, { message: "الاسم مطلوب" }),
  email: z.string().min(1, { message: "البريد الإلكتروني مطلوب" }).email({ message: "صيغة البريد الإلكتروني غير صحيحة" }),
  password: z.string().optional(),
});

export default function UserForm({
  mode,
  initial,
  onSubmit,
}: {
  mode: "create" | "edit";
  initial?: { name?: string; email?: string };
  onSubmit: (values: { name: string; email: string; password?: string }) => Promise<void> | void;
}) {
  const schema = mode === "create" ? CreateSchema : EditSchema;
  type Values = { name: string; email: string; password?: string };
  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { name: initial?.name ?? "", email: initial?.email ?? "", password: "" },
    mode: "onChange",
  });

  const isSubmitting = form.formState.isSubmitting;

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(async (v) => { await onSubmit(v); })} className="space-y-4">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>الاسم</FormLabel>
              <FormControl>
                <Input type="text" placeholder="اسم المستخدم" {...field} />
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
                <Input type="email" dir="ltr" placeholder="user@example.com" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {mode === "create" && (
          <FormField
            control={form.control}
            name="password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>كلمة المرور</FormLabel>
                <FormControl>
                  <Input type="password" placeholder="********" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        )}

        <Button type="submit" className="w-full" disabled={isSubmitting}>
          {mode === "create" ? "إنشاء" : "حفظ"}
        </Button>
      </form>
    </Form>
  );
}
