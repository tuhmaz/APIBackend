"use client";

import React, {
  createContext,
  useContext,
  useEffect,
  useState,
  ReactNode,
} from "react";
import { apiLogin, apiRegister, apiForgotPassword, apiMe, apiLogout } from "@/lib/api";

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  roles?: { name: string }[];
}

interface AuthContextValue {
  user: AuthUser | null;
  token: string | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (
    name: string,
    email: string,
    password: string,
    password_confirmation: string
  ) => Promise<void>;
  logout: () => Promise<void>;
  forgotPassword: (email: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

const TOKEN_KEY = "alhurani_token";

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  // تحميل التوكن من localStorage
  useEffect(() => {
    const stored = typeof window !== "undefined" ? localStorage.getItem(TOKEN_KEY) : null;
    const cookieToken = typeof document !== "undefined"
      ? (document.cookie.split(";").map((c) => c.trim()).find((c) => c.startsWith("token="))?.split("=")[1] ?? null)
      : null;
    const initial = stored ?? cookieToken;
    if (initial) {
      setToken(initial);
      apiMe(initial)
        .then((u) => setUser(u))
        .catch(() => {
          setUser(null);
          setToken(null);
          localStorage.removeItem(TOKEN_KEY);
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = async (email: string, password: string) => {
    setLoading(true);
    try {
      const res = await apiLogin(email, password);
      setToken(res.token);
      setUser(res.user);
      localStorage.setItem(TOKEN_KEY, res.token);
      if (typeof document !== "undefined") {
        document.cookie = `token=${res.token}; path=/; max-age=${60 * 60 * 24 * 7}`; // 7 أيام
      }
    } finally {
      setLoading(false);
    }
  };

  const register = async (
    name: string,
    email: string,
    password: string,
    password_confirmation: string
  ) => {
    setLoading(true);
    try {
      const res = await apiRegister(name, email, password, password_confirmation);
      setToken(res.token);
      setUser(res.user);
      localStorage.setItem(TOKEN_KEY, res.token);
      if (typeof document !== "undefined") {
        document.cookie = `token=${res.token}; path=/; max-age=${60 * 60 * 24 * 7}`;
      }
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    if (token) {
      try {
        await apiLogout(token);
      } catch {
        // حتى لو فشل، نحذف التوكن محلياً
      }
    }
    setUser(null);
    setToken(null);
    if (typeof window !== "undefined") {
      localStorage.removeItem(TOKEN_KEY);
      document.cookie = "token=; path=/; max-age=0";
    }
  };

  const forgotPassword = async (email: string) => {
    await apiForgotPassword(email);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        loading,
        login,
        register,
        logout,
        forgotPassword,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
