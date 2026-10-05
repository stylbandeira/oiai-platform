import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CheckCircle } from "lucide-react";
import { useLocation } from "react-router-dom";
import { useState } from "react";
import api from "@/lib/api";
import { Button } from "@/components/ui/button";

function maskEmail(email: string): string {
    const [name, domain] = email.split("@");
    if (!name || !domain) return email;
    return `${name.slice(0, 3)}***@${domain}`;
}

export default function EmailConfirmationScreen() {
    const location = useLocation();
    const [email] = useState<string>(location.state?.email ?? "");
    const [sending, setSending] = useState(false);
    const [message, setMessage] = useState("");

    const resendEmail = async () => {
        setSending(true);
        setMessage("");
        try {
            await api.post("/email/verification-notification");
            setMessage("E-mail reenviado. Verifique sua caixa de entrada.");
        } catch (error: any) {
            setMessage(error.response?.data?.message ?? "Não foi possível reenviar agora.");
        } finally {
            setSending(false);
        }
    };

    return (
        <div className="flex items-center justify-center min-h-screen bg-background p-4">
            <Card className="max-w-md w-full text-center shadow-lg border-0">
                <CardHeader>
                    <div className="flex justify-center mb-4">
                        <CheckCircle className="w-12 h-12 text-green-500" />
                    </div>
                    <CardTitle className="text-2xl font-bold">Confirmação de E-mail</CardTitle>
                    <p className="text-muted-foreground mt-2">
                        Enviamos um link de confirmação para:
                    </p>
                    {email && <p className="font-medium mt-2">{maskEmail(email)}</p>}
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Verifique sua caixa de entrada (e a pasta de spam também).
                    </p>
                    <Button className="mt-4" onClick={resendEmail} disabled={sending}>
                        {sending ? "Reenviando..." : "Reenviar e-mail"}
                    </Button>
                    {message && <p className="text-sm text-muted-foreground mt-3">{message}</p>}
                </CardContent>
            </Card>
        </div>
    );
}
