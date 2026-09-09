import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ShoppingCart, Building2, Shield, Loader2 } from "lucide-react";
import api from "@/lib/api";
import { useUser } from "@/contexts/UserContext";
import { CustomLogo } from "../oiai_ui/CustomLogo";
import { UserType } from "@/types/user";

interface LoginFormProps {
  onSwitchToRegister: () => void;
}

export function LoginForm({ onSwitchToRegister }: LoginFormProps) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [userType, setUserType] = useState<UserType>("client");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { login } = useUser();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (isLoading) return;

    if (!email || !password) {
      setError("Por favor, preencha todos os campos");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await api.post("/login", {
        email,
        password,
        user_type: userType
      });

      await new Promise(resolve => setTimeout(resolve, 500));

      login(response.data.token, {
        type: response.data.user.type || userType,
        name: response.data.user.name,
        email: response.data.user.email,
        points: response.data.user.points
      });

    } catch (error: any) {
      console.error("Login error:", error);
      setError(
        error.response?.data?.message ||
        "Erro ao fazer login. Verifique suas credenciais e tente novamente."
      );
    } finally {
      setIsLoading(false);
    }
  };

  const getUserTypeIcon = (type: UserType) => {
    switch (type) {
      case "client":
        return <ShoppingCart className="w-4 h-4" />;
      case "company":
        return <Building2 className="w-4 h-4" />;
      case "admin":
        return <Shield className="w-4 h-4" />;
    }
  };

  return (
    <Card className="w-full max-w-md shadow-medium border-0">
      <CardHeader className="text-center pb-4">
        <div className="w-16 h-16 bg-gradient-primary rounded-full mx-auto mb-4 flex items-center justify-center">
          <CustomLogo className="w-8 h-8 text-white" />
        </div>
        {/* <CardTitle className="text-2xl font-bold">Óiaí</CardTitle> */}
        <p className="text-muted-foreground">Entre na sua conta</p>
      </CardHeader>

      <CardContent className="space-y-4">
        {error && (
          <div className="p-3 text-sm text-red-500 bg-red-50 rounded-md">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="userType">Tipo de usuário</Label>
            <Select
              value={userType}
              onValueChange={(value: UserType) => setUserType(value)}
              disabled={isLoading}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="client">
                  <div className="flex items-center gap-2">
                    {getUserTypeIcon("client")}
                    Cliente
                  </div>
                </SelectItem>
                <SelectItem value="company">
                  <div className="flex items-center gap-2">
                    {getUserTypeIcon("company")}
                    Empresa
                  </div>
                </SelectItem>
                <SelectItem value="admin">
                  <div className="flex items-center gap-2">
                    {getUserTypeIcon("admin")}
                    Administrador
                  </div>
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="seu@email.com"
              required
              disabled={isLoading}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="password">Senha</Label>
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
              disabled={isLoading}
            />
          </div>

          <Button
            type="submit"
            className={`w-full transition-all duration-300 ${isLoading
              ? "bg-gray-400 cursor-not-allowed"
              : "bg-gradient-primary hover:shadow-glow"}`}
            disabled={isLoading}
          >
            {isLoading ? (
              <div className="flex items-center justify-center gap-2">
                <Loader2 className="h-4 w-4 animate-spin" />
                Entrando...
              </div>
            ) : "Entrar"}
          </Button>
        </form>

        <div className="text-center">
          <p className="text-sm text-muted-foreground">
            Não tem uma conta?{" "}
            <button
              onClick={onSwitchToRegister}
              className="text-primary hover:underline font-medium"
              disabled={isLoading}
            >
              Cadastre-se
            </button>
          </p>
        </div>
      </CardContent>
    </Card>
  );
}