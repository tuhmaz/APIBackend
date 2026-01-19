"use client";

import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";

const CreateSchema = z.object({
  name: z.string().min(2, { message: "الاسم قصير جدًا" }).max(100, { message: "الاسم طويل جدًا" }),
  guard_name: z.string().min(3).max(50),
});

type Values = z.infer<typeof CreateSchema>;

export default function PermissionForm({
  mode,
  initial,
  action,
}: {
  mode: "create" | "edit";
  initial?: { name?: string; guard_name?: string };
  action: (fd: FormData) => Promise<void>;
}) {
  const form = useForm<Values>({
    resolver: zodResolver(CreateSchema),
    defaultValues: {
      name: initial?.name ?? "",
      guard_name: initial?.guard_name ?? "sanctum",
    },
    mode: "onChange",
  });

  return (
    <Form {...form}>
      <form action={action} className="space-y-4">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>اسم الصلاحية</FormLabel>
              <FormControl>
                <Input placeholder="مثال: manage users" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="guard_name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>الحارس</FormLabel>
              <FormControl>
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger>
                    <SelectValue placeholder="اختر الحارس" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="sanctum">sanctum</SelectItem>
                    <SelectItem value="api">api</SelectItem>
                  </SelectContent>
                </Select>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {/* قيم مخفية لإرسالها عبر Server Action */}
        <input type="hidden" name="name" value={form.getValues("name")} />
        <input type="hidden" name="guard_name" value={form.getValues("guard_name")} />

        <Button type="submit" className="w-full">{mode === "create" ? "حفظ" : "تحديث"}</Button>
      </form>
    </Form>
  );
}
