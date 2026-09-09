import { useState } from "react";
import { LoginForm, UserType } from "@/components/auth/LoginForm";
import { RegisterForm } from "@/components/auth/RegisterForm";

export default function Auth() {
  const [isLogin, setIsLogin] = useState(true);

  console.log('Auth render - isLogin', isLogin);

  return (
    <div className="min-h-screen bg-gradient-soft flex items-center justify-center p-4">
      <div className="w-full max-w-md" key={isLogin ? 'login' : 'register'}>
        {isLogin ? (
          <LoginForm onSwitchToRegister={() => {
            console.log('Switching to register'); // ← DEBUG
            setIsLogin(false);
          }} />
        ) : (
          <RegisterForm onSwitchToLogin={() => {
            console.log('Switching to login'); // ← DEBUG
            setIsLogin(true);
          }} />
        )}
      </div>
    </div>
  );
}