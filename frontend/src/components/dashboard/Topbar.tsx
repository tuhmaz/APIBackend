"use client";

import { Badge } from "@/components/ui/badge";
import UserMenu from "./UserMenu";
import { ThemeToggle } from "@/components/theme-toggle";

interface TopbarProps {
  title?: string;
  description?: string;
}

export default function Topbar({ title = "لوحة التحكم", description = "واجهة Next.js متصلة بـ Laravel API." }: TopbarProps) {
  return (
    <div className="flex items-center justify-between">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
        {description && (
          <p className="text-sm text-muted-foreground">{description}</p>
        )}
      </div>
      <div className="flex items-center gap-2">
        <Badge variant="outline">إصدار API</Badge>
        <ThemeToggle />
        <UserMenu />
      </div>
    </div>
  );
}

