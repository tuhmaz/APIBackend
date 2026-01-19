"use client";

import { Table, TableHeader, TableRow, TableHead, TableBody } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from "@/components/ui/dialog";

type Permission = { id: number; name: string; guard_name?: string };

export default function PermissionsTable({ permissions, onDelete }: { permissions: Permission[]; onDelete: (fd: FormData) => Promise<void> }) {
  return (
    <div className="rounded-xl border bg-white">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>الرقم</TableHead>
            <TableHead>الاسم</TableHead>
            <TableHead>الحارس</TableHead>
            <TableHead>إجراءات</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {permissions.map((p) => (
            <TableRow key={p.id}>
              <td className="px-4 py-2">{p.id}</td>
              <td className="px-4 py-2">{p.name}</td>
              <td className="px-4 py-2">{p.guard_name ?? "-"}</td>
              <td className="px-4 py-2 flex gap-2">
                <a href={`/dashboard/permissions/${p.id}/edit`} className="inline-block">
                  <Button size="sm" variant="outline">تعديل</Button>
                </a>
                <Dialog>
                  <DialogTrigger asChild>
                    <Button size="sm" variant="destructive">حذف</Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>تأكيد الحذف</DialogTitle>
                    </DialogHeader>
                    <div className="text-sm">هل أنت متأكد من حذف الصلاحية: {p.name}؟ لا يمكن التراجع.</div>
                    <DialogFooter>
                      <DialogClose asChild>
                        <Button variant="outline" size="sm">إلغاء</Button>
                      </DialogClose>
                      <form action={onDelete}>
                        <input type="hidden" name="id" value={String(p.id)} />
                        <Button size="sm" variant="destructive" type="submit">تأكيد الحذف</Button>
                      </form>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </td>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

