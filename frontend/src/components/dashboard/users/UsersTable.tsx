"use client";

import Link from "next/link";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import type { User } from "@/lib/api/users";

interface UsersTableProps {
  users: User[];
  onDelete: (formData: FormData) => Promise<void>;
}

export default function UsersTable({ users, onDelete }: UsersTableProps) {
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-semibold tracking-tight">إدارة المستخدمين</h2>
        <Button asChild>
          <Link href="/dashboard/users/create">
            <span className="ml-2">+</span>
            إنشاء مستخدم جديد
          </Link>
        </Button>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-[200px]">الاسم الكامل</TableHead>
              <TableHead>البريد الإلكتروني</TableHead>
              <TableHead>الأدوار</TableHead>
              <TableHead className="text-left w-[200px]">الإجراءات</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {users.map((user) => (
              <TableRow key={user.id}>
                <TableCell className="font-medium">{user.name}</TableCell>
                <TableCell dir="ltr" className="font-mono text-sm">
                  {user.email}
                </TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-1">
                    {user.roles?.map((role) => (
                      <Badge key={role.name} variant="secondary">
                        {role.name}
                      </Badge>
                    )) ?? <span className="text-muted-foreground text-sm">لا توجد أدوار</span>}
                  </div>
                </TableCell>
                <TableCell>
                  <div className="flex items-center gap-2">
                    <Button asChild size="sm" variant="outline">
                      <Link href={`/dashboard/users/${user.id}`}>عرض</Link>
                    </Button>
                    
                    <Button asChild size="sm" variant="secondary">
                      <Link href={`/dashboard/users/${user.id}/edit`}>تعديل</Link>
                    </Button>

                    <Dialog>
                      <DialogTrigger asChild>
                        <Button size="sm" variant="destructive">حذف</Button>
                      </DialogTrigger>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>تأكيد حذف المستخدم</DialogTitle>
                          <DialogDescription>
                            هل أنت متأكد من رغبتك في حذف المستخدم: {user.name}؟
                            <br />
                            <span className="text-destructive font-medium">
                              لا يمكن التراجع عن هذا الإجراء.
                            </span>
                          </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                          <DialogClose asChild>
                            <Button variant="outline" size="sm">إلغاء</Button>
                          </DialogClose>
                          <form action={onDelete}>
                            <input type="hidden" name="id" value={String(user.id)} />
                            <Button size="sm" variant="destructive" type="submit">
                              تأكيد الحذف
                            </Button>
                          </form>
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

      {users.length === 0 && (
        <div className="text-center py-12 text-muted-foreground">
          <p>لا توجد مستخدمين مسجلين بعد</p>
          <Button asChild variant="link" className="mt-2">
            <Link href="/dashboard/users/create">إنشاء أول مستخدم</Link>
          </Button>
        </div>
      )}
    </div>
  );
}