"use client";

import { useState } from "react";
import NavItem from "./NavItem";
import {
  Home,
  Users,
  GraduationCap,
  FileText,
  Settings,
  BookOpen,
  Calendar,
  MessageSquare,
  BarChart3,
  Shield,
  Bell,
  ChevronDown,
  ChevronRight,
} from "lucide-react";
import { cn } from "@/lib/utils";

interface NavGroup {
  title: string;
  items: {
    href: string;
    label: string;
    icon: React.ReactNode;
  }[];
}

export default function Sidebar() {
  const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>({
    main: true,
    content: true,
    users: true,
    system: false,
  });

  const toggleGroup = (groupKey: string) => {
    setExpandedGroups((prev) => ({
      ...prev,
      [groupKey]: !prev[groupKey],
    }));
  };

  const navGroups: Record<string, NavGroup> = {
    main: {
      title: "الرئيسية",
      items: [
        { href: "/dashboard", label: "لوحة التحكم", icon: <Home className="size-4" /> },
        { href: "/dashboard/analytics", label: "الإحصائيات", icon: <BarChart3 className="size-4" /> },
      ],
    },
    content: {
      title: "المحتوى",
      items: [
        { href: "/dashboard/posts", label: "المقالات", icon: <FileText className="size-4" /> },
        { href: "/dashboard/school-classes", label: "الصفوف الدراسية", icon: <GraduationCap className="size-4" /> },
        { href: "/dashboard/subjects", label: "المواد", icon: <BookOpen className="size-4" /> },
        { href: "/dashboard/calendar", label: "التقويم", icon: <Calendar className="size-4" /> },
      ],
    },
    users: {
      title: "المستخدمون",
      items: [
        { href: "/dashboard/users", label: "إدارة المستخدمين", icon: <Users className="size-4" /> },
        { href: "/dashboard/messages", label: "الرسائل", icon: <MessageSquare className="size-4" /> },
        { href: "/dashboard/notifications", label: "الإشعارات", icon: <Bell className="size-4" /> },
      ],
    },
    system: {
      title: "النظام",
      items: [
        { href: "/dashboard/settings", label: "الإعدادات", icon: <Settings className="size-4" /> },
        { href: "/dashboard/security", label: "الأمان", icon: <Shield className="size-4" /> },
        { href: "/dashboard/permissions", label: "الصلاحيات", icon: <Shield className="size-4" /> },
      ],
    },
  };

  return (
    <aside className="flex h-full flex-col">
      {/* Logo/Brand */}
      <div className="border-b px-6 py-4">
        <div className="flex items-center gap-3">
          <div className="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
            <GraduationCap className="size-5" />
          </div>
          <div className="flex-1">
            <h1 className="text-base font-bold leading-tight">نظام الحوراني</h1>
            <p className="text-xs text-muted-foreground">لوحة التحكم</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto px-3 py-4">
        <div className="space-y-6">
          {Object.entries(navGroups).map(([key, group]) => (
            <div key={key}>
              {/* Group Header */}
              <button
                onClick={() => toggleGroup(key)}
                className={cn(
                  "flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wider transition-colors",
                  "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                )}
              >
                <span>{group.title}</span>
                {expandedGroups[key] ? (
                  <ChevronDown className="size-3.5" />
                ) : (
                  <ChevronRight className="size-3.5" />
                )}
              </button>

              {/* Group Items */}
              {expandedGroups[key] && (
                <div className="mt-2 space-y-1">
                  {group.items.map((item) => (
                    <NavItem
                      key={item.href}
                      href={item.href}
                      label={item.label}
                      icon={item.icon}
                    />
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      </nav>

      {/* Footer */}
      <div className="border-t px-4 py-3">
        <div className="rounded-lg bg-muted/50 p-3 text-center">
          <p className="text-xs font-medium text-muted-foreground">نسخة النظام</p>
          <p className="text-xs font-bold">v1.0.0</p>
        </div>
      </div>
    </aside>
  );
}
