import {
  apiLogin,
  apiRegister,
  apiForgotPassword,
  apiResetPassword,
  apiMe,
  apiLogout,
} from "@/lib/api";

export type AuthUser = { id: number; name: string; email: string };

export type LoginPayload = { email: string; password: string; remember?: boolean };
export type RegisterPayload = { name: string; email: string; password: string; password_confirmation: string };
export type ForgotPasswordPayload = { email: string };
export type ResetPasswordPayload = { email: string; token: string; password: string; password_confirmation: string };

export type Result<T> = { ok: true; data: T } | { ok: false; error: string };

let authToken: string | null = null;
const TOKEN_KEY = "auth_token";

function getToken(): string | null {
  if (authToken) return authToken;
  if (typeof window !== "undefined") {
    const t = window.localStorage.getItem(TOKEN_KEY);
    authToken = t || null;
    return authToken;
  }
  return null;
}

function setToken(token: string) {
  authToken = token;
  if (typeof window !== "undefined") {
    window.localStorage.setItem(TOKEN_KEY, token);
  }
}

function clearToken() {
  authToken = null;
  if (typeof window !== "undefined") {
    window.localStorage.removeItem(TOKEN_KEY);
  }
}

function toError(e: unknown): string {
  if (e instanceof Error) return e.message || "حدث خطأ";
  return "حدث خطأ غير متوقع";
}

export async function login(payload: LoginPayload): Promise<Result<void>> {
  try {
    const res = await apiLogin(payload.email, payload.password);
    setToken(res.token);
    return { ok: true, data: undefined };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function register(payload: RegisterPayload): Promise<Result<void>> {
  try {
    const res = await apiRegister(
      payload.name,
      payload.email,
      payload.password,
      payload.password_confirmation
    );
    setToken(res.token);
    return { ok: true, data: undefined };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function logout(): Promise<Result<void>> {
  try {
    const token = getToken();
    if (token) {
      await apiLogout(token);
    }
    clearToken();
    return { ok: true, data: undefined };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function forgotPassword(payload: ForgotPasswordPayload): Promise<Result<void>> {
  try {
    await apiForgotPassword(payload.email);
    return { ok: true, data: undefined };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function resetPassword(payload: ResetPasswordPayload): Promise<Result<void>> {
  try {
    await apiResetPassword(payload);
    return { ok: true, data: undefined };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function resendEmailVerification(): Promise<Result<void>> {
  try {
    return { ok: false, error: "غير مدعوم في واجهة API الحالية" };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function verifyEmail(): Promise<Result<void>> {
  try {
    return { ok: false, error: "غير مدعوم في واجهة API الحالية" };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

export async function me(): Promise<Result<AuthUser>> {
  try {
    const token = getToken();
    if (!token) return { ok: false, error: "غير مسجل الدخول" };
    const user = await apiMe(token);
    return { ok: true, data: user };
  } catch (e) {
    return { ok: false, error: toError(e) };
  }
}

