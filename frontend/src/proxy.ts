import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

async function isAuthenticated(req: NextRequest): Promise<boolean> {
  const token = req.cookies.get("token")?.value ?? null;
  const xsrf = req.cookies.get("XSRF-TOKEN")?.value ?? null;
  const laravel = req.cookies.get("laravel_session")?.value ?? null;
  const rawBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
  const base = rawBase.replace(/\/+$/, "");
  const prefix = base.endsWith("/api") ? "" : "/api";
  const url = `${base}${prefix}/auth/user`;
  try {
    const cookieHeader = req.headers.get("cookie") ?? "";
    if (token) {
      const res = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
          "X-Requested-With": "XMLHttpRequest",
        },
      });
      if (res.ok) return true;
    }
    if (xsrf || laravel) {
      const res = await fetch(url, {
        method: "GET",
        headers: {
          Accept: "application/json",
          Cookie: cookieHeader,
          "X-Requested-With": "XMLHttpRequest",
          ...(xsrf ? { "X-XSRF-TOKEN": decodeURIComponent(xsrf) } : {}),
        },
      });
      if (res.ok) return true;
    }
  } catch {}
  return false;
}

export async function proxy(req: NextRequest) {
  const { pathname } = req.nextUrl;
  const isDashboard = pathname.startsWith("/dashboard");
  const isAuthPage = pathname === "/login" || pathname === "/register" || pathname === "/forgot-password" || pathname.startsWith("/verify-email");

  if (isDashboard || pathname.startsWith("/users")) {
    const ok = await isAuthenticated(req);
    if (!ok) {
      const url = new URL("/login", req.url);
      return NextResponse.redirect(url);
    }
  }

  if (isAuthPage) {
    const ok = await isAuthenticated(req);
    if (ok) {
      const url = new URL("/dashboard", req.url);
      return NextResponse.redirect(url);
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/dashboard/:path*",
    "/users/:path*",
    "/login",
    "/register",
    "/forgot-password",
    "/verify-email",
  ],
};
