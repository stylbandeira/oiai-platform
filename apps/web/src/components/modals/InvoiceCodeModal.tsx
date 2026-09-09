import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { AlertCircle, QrCode } from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import api from "@/lib/api";

interface InvoiceCodeModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess?: (data: any) => void;
    onError?: (error: string) => void;
}

export function InvoiceCodeModal({ isOpen, onClose, onSuccess, onError }: InvoiceCodeModalProps) {
    const [code, setCode] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!code.trim()) {
            setError('Por favor, insira o código do QR Code');
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await api.post('/invoice/process', {
                invoice_code: code.trim()
            });

            if (onSuccess) {
                onSuccess(response.data);
            }

            // Limpar e fechar
            setCode('');
            onClose();

        } catch (err: any) {
            const errorMessage = err.response?.data?.error ||
                err.response?.data?.message ||
                'Erro ao processar nota fiscal';

            setError(errorMessage);

            if (onError) {
                onError(errorMessage);
            }
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        setCode('');
        setError(null);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => {
            if (!open) handleClose();
        }}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        <div className="flex items-center gap-2">
                            <QrCode className="h-5 w-5" />
                            <span>Inserir Código da Nota Fiscal</span>
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="qr-code">
                            Código:
                        </Label>
                        <Input
                            id="qr-code"
                            placeholder="Cole ou digite o código do QR Code aqui..."
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            disabled={loading}
                            className="font-mono text-sm"
                        />
                        <p className="text-xs text-muted-foreground">
                            Insira o código completo que aparece na nota fiscal
                        </p>
                    </div>

                    {error && (
                        <div className="bg-destructive/10 text-destructive p-3 rounded-md flex items-start gap-2">
                            <AlertCircle className="h-5 w-5 mt-0.5 flex-shrink-0" />
                            <div className="flex-1 text-sm">
                                {error}
                            </div>
                        </div>
                    )}

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={loading}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={loading || !code.trim()}
                        >
                            {loading ? (
                                <>
                                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                    Processando...
                                </>
                            ) : 'Processar Nota'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
