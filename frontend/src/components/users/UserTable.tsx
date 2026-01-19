"use client";

import Link from "next/link";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import type { User } from "@/lib/api/users";

export default function UserTable({ users }: { users: User[] }) {
  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">المستخدمون</h2>
        <Button asChild>
          <Link href="/dashboard/users/create">إنشاء مستخدم</Link>
        </Button>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>الاسم</TableHead>
            <TableHead>البريد الإلكتروني</TableHead>
            <TableHead>الإجراءات</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {users.map((u) => (
            <TableRow key={u.id}>
              <TableCell>{u.name}</TableCell>
              <TableCell dir="ltr">{u.email}</TableCell>
              <TableCell className="space-x-2">
                <Button asChild size="sm" variant="outline">
                  <Link href={`/dashboard/users/${u.id}`}>عرض</Link>
                </Button>
                <Button asChild size="sm" variant="secondary">
                  <Link href={`/dashboard/users/${u.id}/edit`}>تعديل</Link>
                </Button>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
