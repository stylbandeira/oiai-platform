import { useState } from "react";
import { LoginForm } from "@/components/auth/LoginForm";
import type { UserType } from "@/types/user";
import { RegisterForm } from "@/components/auth/RegisterForm";

export default function Auth({ initialMode = "login" }: { initialMode?: "login" | "register" }) {
  const [isLogin, setIsLogin] = useState(initialMode === "login");

  return (
    <div className="min-h-screen bg-gradient-soft flex items-center justify-center p-4">
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
