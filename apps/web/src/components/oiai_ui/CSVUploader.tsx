// components/CSVUploader.tsx
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Upload, Loader2 } from "lucide-react";

interface CSVUploaderProps {
    onFileSubmit: (file: File) => Promise<void>;
    accept?: string;
    buttonText?: string;
    disabled?: boolean;
}

export function CSVUploader({
    onFileSubmit,
    accept = ".csv",
    buttonText = "Enviar CSV",
    disabled = false
}: CSVUploaderProps) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [message, setMessage] = useState("");

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setSelectedFile(file);
            setMessage("");
        }
    };

    const handleUpload = async () => {
        if (!selectedFile) return;

        setIsUploading(true);
        setMessage("");

        try {
            await onFileSubmit(selectedFile);
            setMessage("✅ Arquivo processado com sucesso!");
            setSelectedFile(null);

            // Limpa o input
            const fileInput = document.getElementById("csv-file") as HTMLInputElement;
            if (fileInput) fileInput.value = "";

        } catch (error: any) {
            setMessage(`❌ Erro: ${error.response?.data?.message || error.message || "Falha no upload"}`);
        } finally {
            setIsUploading(false);
        }
    };

    return (
        <div className="space-y-4 p-4 border rounded-lg">
            <div className="flex items-center gap-4">
                <Input
                    id="csv-file"
                    type="file"
                    accept={accept}
                    onChange={handleFileSelect}
                    disabled={disabled || isUploading}
                    className="max-w-xs"
                />

                <Button
                    onClick={handleUpload}
                    disabled={!selectedFile || isUploading || disabled}
                    className="flex items-center gap-2"
                >
                    {isUploading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <Upload className="h-4 w-4" />
                    )}
                    {isUploading ? "Enviando..." : buttonText}
                </Button>
            </div>

            {selectedFile && (
                <p className="text-sm">📄 Arquivo: {selectedFile.name}</p>
            )}

            {message && (
                <p className={`text-sm ${message.includes('✅') ? 'text-green-600' : 'text-red-600'}`}>
                    {message}
                </p>
            )}
        </div>
    );
}