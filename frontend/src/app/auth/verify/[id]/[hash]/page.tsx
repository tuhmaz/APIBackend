import { apiVerifyEmail } from "@/lib/api";

export default async function VerifyEmailPage({ params }: { params: { id: string; hash: string } }) {
  const { id, hash } = params;
  let ok = false;
  try {
    await apiVerifyEmail(id, hash);
    ok = true;
  } catch {
    ok = false;
  }
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100">
      <div className={ok ? "p-10 text-center text-green-600" : "p-10 text-center text-red-600"}>
        {ok ? "تم تفعيل بريدك الإلكتروني بنجاح" : "فشل التفعيل"}
      </div>
    </div>
  );
}
