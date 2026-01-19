import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";

export default function UserCard({ user }: { user: { name: string; email: string } }) {
  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>{user.name}</CardTitle>
        <CardDescription dir="ltr">{user.email}</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="text-sm text-muted-foreground">معلومات المستخدم</div>
      </CardContent>
    </Card>
  );
}
