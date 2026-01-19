"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

type Permission = { id: number; name: string };

export default function RoleForm({
  initial,
  permissions,
  action,
}: {
  initial?: { name?: string; permissionIds?: number[] };
  permissions: Permission[];
  action: (fd: FormData) => Promise<void>;
}) {
  const form = useForm<{ name: string }>({
    defaultValues: { name: initial?.name ?? "" },
    mode: "onChange",
  });
  const [selected, setSelected] = useState<number[]>(initial?.permissionIds ?? []);

  function toggle(id: number, checked: boolean) {
    setSelected((prev) => {
      const set = new Set(prev);
      if (checked) set.add(id); else set.delete(id);
      return Array.from(set);
    });
  }

  return (
    <Form {...form}>
      <form action={action} className="space-y-4">
        <FormField control={form.control} name="name" render={({ field }) => (
          <FormItem>
            <FormLabel>اسم الدور</FormLabel>
            <FormControl>
              <Input placeholder="مثال: Admin" {...field} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )} />

        <div>
          <div className="mb-2 text-sm">الصلاحيات</div>
          <div className="grid grid-cols-2 gap-2">
            {permissions.map((p) => {
              const checked = selected.includes(p.id);
              return (
                <label key={p.id} className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    name="permissions[]"
                    value={String(p.id)}
                    checked={checked}
                    onChange={(e) => toggle(p.id, e.target.checked)}
                  />
                  <span>{p.name}</span>
                </label>
              );
            })}
          </div>
        </div>

        {selected.map((id) => (
          <input key={id} type="hidden" name="permissions[]" value={String(id)} />
        ))}

        <Button type="submit" className="w-full">حفظ</Button>
      </form>
    </Form>
  );
}
