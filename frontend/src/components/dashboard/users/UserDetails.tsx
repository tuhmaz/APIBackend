"use client";

import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
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
import type { User } from "@/lib/api/users";

interface UserDetailsProps {
  user: User;
  onDelete?: (formData: FormData) => Promise<void>;
  showActions?: boolean;
}

export default function UserDetails({ user, onDelete, showActions = true }: UserDetailsProps) {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">تفاصيل المستخدم</h1>
          <p className="text-muted-foreground">معلومات كاملة عن المستخدم</p>
        </div>
        
        {showActions && (
          <div className="flex items-center gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href="/dashboard/users">العودة للقائمة</Link>
            </Button>
            
            <Button asChild variant="secondary" size="sm">
              <Link href={`/dashboard/users/${user.id}/edit`}>تعديل</Link>
            </Button>

            {onDelete && (
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
            )}
          </div>
        )}
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>المعلومات الأساسية</CardTitle>
            <CardDescription>تفاصيل الحساب الرئيسية</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <span className="text-sm font-medium text-muted-foreground">الاسم الكامل</span>
              <p className="text-lg font-semibold">{user.name}</p>
            </div>
            
            <div>
              <span className="text-sm font-medium text-muted-foreground">البريد الإلكتروني</span>
              <p className="text-lg font-mono" dir="ltr">{user.email}</p>
            </div>
            
            <div>
              <span className="text-sm font-medium text-muted-foreground">معرف المستخدم</span>
              <p className="text-lg font-mono">#{user.id}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>الأدوار والصلاحيات</CardTitle>
            <CardDescription>الأدوار الممنوحة لهذا المستخدم</CardDescription>
          </CardHeader>
          <CardContent>
            {user.roles && user.roles.length > 0 ? (
              <div className="flex flex-wrap gap-2">
                {user.roles.map((role) => (
                  <Badge key={role.name} variant="secondary" className="text-sm">
                    {role.name}
                  </Badge>
                ))}
              </div>
            ) : (
              <p className="text-muted-foreground">لا توجد أدوار مخصصة لهذا المستخدم</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>الإجراءات السريعة</CardTitle>
          <CardDescription>إدارة المستخدم بسرعة</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline">
              <Link href={`/dashboard/users/${user.id}/edit`}>
                تعديل المعلومات
              </Link>
            </Button>
            
            <Button asChild variant="outline">
              <Link href="/dashboard/users">
                عرض جميع المستخدمين
              </Link>
            </Button>
            
            {onDelete && (
              <Dialog>
                <DialogTrigger asChild>
                  <Button variant="destructive">حذف المستخدم</Button>
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
                      <Button variant="outline">إلغاء</Button>
                    </DialogClose>
                    <form action={onDelete}>
                      <input type="hidden" name="id" value={String(user.id)} />
                      <Button variant="destructive" type="submit">
                        تأكيد الحذف
                      </Button>
                    </form>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}