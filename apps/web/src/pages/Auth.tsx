import { useState } from "react";
import { LoginForm } from "@/components/auth/LoginForm";
import type { UserType } from "@/types/user";
import { RegisterForm } from "@/components/auth/RegisterForm";
import { ArrowLeft } from "lucide-react";

export default function Auth({ initialMode = "login" }: { initialMode?: "login" | "register" }) {
  const [isLogin, setIsLogin] = useState(initialMode === "login");

  return (
    <div className="relative min-h-screen bg-gradient-soft flex items-center justify-center p-4">
      <a
        href="/"
        className="absolute left-5 top-5 inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-card hover:text-foreground sm:left-8 sm:top-8"
      >
        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
        Voltar para a Home
      </a>
      <div className="w-full max-w-md" key={isLogin ? 'login' : 'register'}>
        {isLogin ? (
          <LoginForm onSwitchToRegister={() => setIsLogin(false)} />
        ) : (
          <RegisterForm onSwitchToLogin={() => setIsLogin(true)} />
        )}
      </div>
    </div>
  );
}
