"use client";

import { useState } from "react";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";

type Role = { id: number; name: string; users_count?: number | null };

export default function RolesTable({ roles, onDelete }: { roles: Role[]; onDelete: (fd: FormData) => Promise<void> }) {
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [openDialogs, setOpenDialogs] = useState<Record<number, boolean>>({});

  const handleDelete = async (id: number) => {
    setDeletingId(id);
    try {
      const formData = new FormData();
      formData.append("id", String(id));
      await onDelete(formData);
      setOpenDialogs({ ...openDialogs, [id]: false });
    } catch (error) {
      console.error("Error deleting role:", error);
    } finally {
      setDeletingId(null);
    }
  };

  if (roles.length === 0) {
    return (
      <div className="rounded-xl border bg-white p-8 text-center">
        <p className="text-muted-foreground">لا توجد أدوار</p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border bg-white overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead className="w-1/3 text-center">الاسم</TableHead>
            <TableHead className="w-1/3 text-center">عدد المستخدمين</TableHead>
            <TableHead className="w-1/3 text-center">الإجراءات</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {roles.map((role) => (
            <TableRow key={role.id}>
              <TableCell className="text-center">
                <Badge variant="secondary" className="text-sm">
                  {role.name}
                </Badge>
              </TableCell>
              <TableCell className="text-center">
                <span className="text-muted-foreground">
                  {role.users_count ?? 0} مستخدم
                </span>
              </TableCell>
              <TableCell className="text-center">
                <div className="flex justify-center space-x-2">
                  <Button size="sm" variant="outline" asChild>
                    <a href={`/dashboard/roles/${role.id}/edit`}>تعديل</a>
                  </Button>

                  <Dialog open={openDialogs[role.id]} onOpenChange={(open) => setOpenDialogs({ ...openDialogs, [role.id]: open })}>
                    <DialogTrigger asChild>
                      <Button
                        size="sm"
                        variant="destructive"
                        disabled={deletingId === role.id}
                      >
                        {deletingId === role.id ? "جاري الحذف..." : "حذف"}
                      </Button>
                    </DialogTrigger>
                    <DialogContent>
                      <DialogHeader>
                        <DialogTitle>تأكيد الحذف</DialogTitle>
                      <DialogDescription>
                        هل أنت متأكد من رغبتك في حذف الدور &quot;{role.name}&quot;؟
                        {role.users_count && role.users_count > 0 && (
                          <span className="block mt-2 text-destructive">
                            ⚠️ هذا الدور لديه {role.users_count} مستخدم وسيتم إزالة الصلاحية منهم.
                          </span>
                        )}
                      </DialogDescription>
                      </DialogHeader>
                      <DialogFooter>
                        <Button variant="outline" onClick={() => setOpenDialogs({ ...openDialogs, [role.id]: false })}>
                          إلغاء
                        </Button>
                        <Button
                          onClick={() => handleDelete(role.id)}
                          variant="destructive"
                        >
                          حذف
                        </Button>
                      </DialogFooter>
                    </DialogContent>
                  </Dialog>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
