"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";

const CreateSchema = z.object({
  name: z.string().min(2, { message: "الاسم يجب أن يكون على الأقل حرفين" }).max(100, { message: "الاسم طويل جدًا" }),
  email: z.string().email({ message: "البريد الإلكتروني غير صالح" }),
  password: z.string().min(6, { message: "كلمة المرور يجب أن تكون على الأقل 6 أحرف" }).optional().or(z.literal("")),
});

const EditSchema = CreateSchema.extend({
  password: z.string().min(6, { message: "كلمة المرور يجب أن تكون على الأقل 6 أحرف" }).optional().or(z.literal("")),
});

type Values = z.infer<typeof CreateSchema>;

export default function UserForm({
  mode,
  initial,
  action,
  userId,
}: {
  mode: "create" | "edit";
  initial?: { name?: string; email?: string };
  action: (userId: string, fd: FormData) => Promise<void>;
  userId?: string;
}) {
  const schema = mode === "create" ? CreateSchema : EditSchema;
  
  const form = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: initial?.name ?? "",
      email: initial?.email ?? "",
      password: "",
    },
    mode: "onChange",
  });

  const onSubmit = (data: Values) => {
    const formData = new FormData();
    formData.append("name", data.name);
    formData.append("email", data.email);
    if (data.password) {
      formData.append("password", data.password);
    }
    if (userId) {
      action(userId, formData);
    } else {
      action("", formData);
    }
  };

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>الاسم الكامل</FormLabel>
              <FormControl>
                <Input placeholder="أدخل الاسم الكامل" {...field} />
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
                <Input type="email" placeholder="example@email.com" {...field} />
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
              <FormLabel>
                {mode === "create" ? "كلمة المرور" : "كلمة المرور الجديدة (اختياري)"}
              </FormLabel>
              <FormControl>
                <Input 
                  type="password" 
                  placeholder={mode === "create" ? "أدخل كلمة المرور" : "أدخل كلمة مرور جديدة"}
                  {...field} 
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <div className="flex items-center gap-2 pt-4">
          <Button 
            type="submit" 
            disabled={!form.formState.isValid || form.formState.isSubmitting}
          >
            {form.formState.isSubmitting ? "جاري الحفظ..." : "حفظ"}
          </Button>
          <Button type="button" variant="outline" asChild>
            <a href="/dashboard/users">إلغاء</a>
          </Button>
        </div>
      </form>
    </Form>
  );
}