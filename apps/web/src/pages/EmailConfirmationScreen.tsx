import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CheckCircle } from "lucide-react";

export default function EmailConfirmationScreen() {
    return (
        <div className="flex items-center justify-center min-h-screen bg-background p-4">
            <Card className="max-w-md w-full text-center shadow-lg border-0">
                <CardHeader>
                    <div className="flex justify-center mb-4">
                        <CheckCircle className="w-12 h-12 text-green-500" />
                    </div>
                    <CardTitle className="text-2xl font-bold">Confirmação de E-mail</CardTitle>
                    <p className="text-muted-foreground mt-2">
                        Enviamos um e-mail com o link de confirmação para você. Verifique sua caixa de entrada (e spam também).
                    </p>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Depois de confirmar seu e-mail, você poderá acessar sua conta normalmente.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
