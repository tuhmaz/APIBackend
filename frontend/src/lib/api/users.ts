import { request } from "@/lib/api";

export type User = {
  id: number;
  name: string;
  email: string;
  roles?: { name: string }[];
};

export async function listUsers(token: string | null): Promise<User[]> {
  return request<User[]>("/dashboard/users", { method: "GET", token });
}

export async function getUser(token: string | null, id: number | string): Promise<User> {
  return request<User>(`/dashboard/users/${id}`, { method: "GET", token });
}

export async function createUser(
  token: string | null,
  data: { name: string; email: string; password: string }
): Promise<User> {
  return request<User>("/dashboard/users", { method: "POST", body: data, token });
}

export async function updateUser(
  token: string | null,
  id: number | string,
  data: { name?: string; email?: string; password?: string }
): Promise<User> {
  return request<User>(`/dashboard/users/${id}`, { method: "PUT", body: data, token });
}

export async function deleteUser(token: string | null, id: number | string): Promise<{ message: string }>{
  return request<{ message: string }>(`/dashboard/users/${id}`, { method: "DELETE", token });
}
