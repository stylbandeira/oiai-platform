import { useState } from "react";
import api from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ShoppingCart, Building2, Shield, UserPlus } from "lucide-react";
import { UserType } from "./LoginForm";
import { useNavigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext"; // Importe o hook

interface RegisterFormProps {
    onSwitchToLogin: () => void;
}

export function RegisterForm({ onSwitchToLogin }: RegisterFormProps) {
    const [name, setName] = useState("");
    const [cpf, setCpf] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [confirmPassword, setConfirmPassword] = useState("");
    const [userType, setUserType] = useState<UserType>("client");
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);

    // Use o hook do contexto
    const { login } = useUser();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (password !== confirmPassword) {
            alert("As senhas não coincidem!");
            return;
        }
        registerUser(userType);
    };

    const registerUser = async (userType: UserType) => {
        setIsLoading(true);
        try {
            const response = await api.post("/register", {
                name,
                cpf,
                userType,
                email,
                password,
                password_confirmation: confirmPassword,
                user_type: userType,
            });

            localStorage.setItem('token', response.data.access_token);

            // Use a função login do contexto em vez de onRegister
            login(response.data.access_token, {
                type: userType,
                name: response.data.user.name,
                email: response.data.user.email,
                points: response.data.user.points || 0
            });

            navigate("/", { state: { fromRegister: true } });

        } catch (error: any) {
            if (error.response) {
                setErrors(error.response.data.errors || {});
            } else {
                alert("Erro inesperado. Tente novamente.");
            }
        } finally {
            setIsLoading(false)
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
                <div className="w-16 h-16 bg-gradient-secondary rounded-full mx-auto mb-4 flex items-center justify-center">
                    <UserPlus className="w-8 h-8 text-white" />
                </div>
                <CardTitle className="text-2xl font-bold">Criar Conta</CardTitle>
                <p className="text-muted-foreground">Junte-se ao Óiaí</p>
            </CardHeader>

            <CardContent className="space-y-4">
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="userType">Tipo de usuário</Label>
                        <Select value={userType} onValueChange={(value: UserType) => setUserType(value)}>
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
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="name">Nome{userType === "company" ? " da Empresa" : ""}</Label>
                        <Input
                            id="name"
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder={userType === "company" ? "Nome da empresa" : "Seu nome completo"}
                            required
                        />
                        {errors.name && <p className="text-red-500 text-sm">{errors.name[0]}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="cpf">CPF</Label>
                        <Input
                            id="cpf"
                            type="text"
                            value={cpf}
                            onChange={(e) => setCpf(e.target.value)}
                            placeholder="CPF do Usuário"
                            required
                        />
                        {errors.cpf && <p className="text-red-500 text-sm">{errors.cpf[0]}</p>}
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
                        />
                        {errors.email && <p className="text-red-500 text-sm">{errors.email[0]}</p>}
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
                        />
                        {errors.password && <p className="text-red-500 text-sm">{errors.password[0]}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="confirmPassword">Confirmar Senha</Label>
                        <Input
                            id="confirmPassword"
                            type="password"
                            value={confirmPassword}
                            onChange={(e) => setConfirmPassword(e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                    </div>

                    <Button
                        type="submit"
                        className="w-full bg-gradient-secondary hover:shadow-glow transition-all duration-300"
                        disabled={isLoading}
                    >
                        {isLoading ? "Criando conta..." : "Criar Conta"}
                    </Button>
                </form>

                <div className="text-center">
                    <p className="text-sm text-muted-foreground">
                        Já tem uma conta?{" "}
                        <button
                            onClick={onSwitchToLogin}
                            className="text-primary hover:underline font-medium"
                        >
                            Faça login
                        </button>
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}