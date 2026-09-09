import { ReactNode, FormEvent } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { ArrowLeft, Save, X } from "lucide-react";
import { useNavigate } from "react-router-dom";

interface FormLayoutProps {
    title: string;
    subtitle?: string;
    children: ReactNode;
    isLoading?: boolean;
    onSave?: (e: FormEvent) => void;
    onCancel?: () => void;
    showBackButton?: boolean;
    saveButtonText?: string;
    cancelButtonText?: string;
    disableSave?: boolean;
    icon?: ReactNode;
}

export function FormLayout({
    title,
    subtitle,
    children,
    isLoading = false,
    onSave,
    onCancel,
    showBackButton = true,
    saveButtonText = "Salvar",
    cancelButtonText = "Cancelar",
    disableSave = false,
    icon
}: FormLayoutProps) {
    const navigate = useNavigate();

    const handleCancel = onCancel || (() => navigate(-1));

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (onSave) {
            onSave(e);
        }
    };

    return (
        <Card className="border-0 shadow-soft">
            <CardHeader className="flex flex-row items-center justify-between">
                <div className="flex items-center gap-3">
                    {showBackButton && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={handleCancel}
                            disabled={isLoading}
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Button>
                    )}
                    <div>
                        <CardTitle className="text-2xl font-bold flex items-center gap-2">
                            {icon}
                            {title}
                        </CardTitle>
                        {subtitle && <p className="text-muted-foreground">{subtitle}</p>}
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {children}

                    <div className="flex gap-3 justify-end pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCancel}
                            disabled={isLoading}
                        >
                            <X className="w-4 h-4 mr-2" />
                            {cancelButtonText}
                        </Button>

                        {onSave && (
                            <Button
                                type="submit"
                                disabled={isLoading || disableSave}
                            >
                                {isLoading ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Salvando...
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-4 h-4 mr-2" />
                                        {saveButtonText}
                                    </>
                                )}
                            </Button>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}