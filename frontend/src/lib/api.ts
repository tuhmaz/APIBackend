const RAW_BASE = process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api";
const API_BASE_URL = RAW_BASE.replace(/\/+$/, "");

type HttpMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface ApiError {
  status: number;
  message: string;
  errors?: Record<string, string[]>;
}

export async function request<T>(
  path: string,
  options: {
    method?: HttpMethod;
    body?: unknown;
    token?: string | null;
  } = {}
): Promise<T> {
  const { method = "GET", body, token } = options;

  const headers: Record<string, string> = {
    Accept: "application/json",
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  };

  // CSRF for Sanctum (if cookie exists in browser)
  if (typeof document !== "undefined") {
    const xsrf = document.cookie
      .split(";")
      .map((c) => c.trim())
      .find((c) => c.startsWith("XSRF-TOKEN="))
      ?.split("=")[1];
    if (xsrf) headers["X-XSRF-TOKEN"] = decodeURIComponent(xsrf);
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const prefix = API_BASE_URL.endsWith("/api") ? "" : "/api";
  const url = `${API_BASE_URL}${prefix}${path}`;
  const res = await fetch(url, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
    credentials: "include",
  });

  if (!res.ok) {
    let message = "Unknown error";
    let errors: Record<string, string[]> | undefined = undefined;

    try {
      const data = await res.json();
      message = data.message ?? data?.data?.message ?? message;
      errors = data.errors ?? data?.data?.errors;
    } catch {
      // لا شيء
    }

    const error: ApiError = {
      status: res.status,
      message,
      errors,
    };
    throw error;
  }

  const json = await res.json();
  const hasData = json && typeof json === "object" && "data" in (json as Record<string, unknown>);
  const payload: unknown = hasData ? (json as Record<string, unknown>)["data"] : json;
  return payload as T;
}

// ===== أنواع بسيطة للـ Auth =====
export interface LoginResponse {
  token: string;
  user: {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
  };
}

export interface RegisterResponse {
  token: string;
  user: {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
  };
}

// ============ دوال Auth ============

export async function apiLogin(email: string, password: string) {
  return request<LoginResponse>("/auth/login", {
    method: "POST",
    body: { email, password },
  });
}

export async function apiRegister(
  name: string,
  email: string,
  password: string,
  password_confirmation: string
) {
  return request<RegisterResponse>("/auth/register", {
    method: "POST",
    body: { name, email, password, password_confirmation },
  });
}

export async function apiForgotPassword(email: string) {
  return request<{ message: string }>("/auth/password/forgot", {
    method: "POST",
    body: { email },
  });
}

export async function apiResetPassword(data: {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}) {
  return request<{ message: string }>("/auth/password/reset", {
    method: "POST",
    body: data,
  });
}

export async function apiMe(token: string) {
  const payload = await request<unknown>("/auth/user", {
    method: "GET",
    token,
  });
  const obj = payload as Record<string, unknown>;
  const user: unknown = obj && typeof obj === "object" && "user" in obj ? obj["user"] : payload;
  return user as LoginResponse["user"];
}

export async function apiLogout(token: string) {
  return request<{ message: string }>("/auth/logout", {
    method: "POST",
    token,
  });
}

// إعادة إرسال رابط التحقق من البريد
export async function apiResendVerification(token: string) {
  return request<{ message: string }>("/auth/email/resend", {
    method: "POST",
    token,
  });
}

// تفعيل البريد الإلكتروني عبر رابط موقّع
export async function apiVerifyEmail(id: string, hash: string) {
  return request<{ message: string }>(`/auth/email/verify/${id}/${hash}`, {
    method: "GET",
  });
}

// بيانات ملخص لوحة التحكم
export interface DashboardSummary {
  posts?: number;
  classes?: number;
  users?: number;
}

export async function apiDashboard(token: string) {
  return request<DashboardSummary>("/dashboard", {
    method: "GET",
    token,
  });
}
