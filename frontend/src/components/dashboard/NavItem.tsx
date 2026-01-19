"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import { ReactNode } from "react";

interface NavItemProps {
  href: string;
  label: string;
  icon?: ReactNode;
}

export default function NavItem({ href, label, icon }: NavItemProps) {
  const pathname = usePathname();

  // تحسين منطق التحقق من الصفحة النشطة
  const isExactMatch = pathname === href;
  const isSubRoute = href !== "/dashboard" && pathname.startsWith(href + "/");
  const active = isExactMatch || isSubRoute;

  return (
    <Link
      href={href}
      className={cn(
        "group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all",
        "hover:bg-accent/80",
        active
          ? "bg-primary text-primary-foreground shadow-sm hover:bg-primary hover:text-primary-foreground"
          : "text-muted-foreground hover:text-foreground"
      )}
    >
      {/* Active Indicator */}
      {active && (
        <span className="absolute right-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-l-full bg-primary-foreground/30" />
      )}

      {/* Icon */}
      <span className={cn(
        "flex-shrink-0 transition-transform",
        active && "scale-110"
      )}>
        {icon}
      </span>

      {/* Label */}
      <span className="truncate">{label}</span>
    </Link>
  );
}

