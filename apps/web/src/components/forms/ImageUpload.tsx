import { useState, useRef } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { X, Upload, Image as ImageIcon } from "lucide-react";
import { validateImage, readFileAsDataURL, compressImage } from "@/utils/fileUtils";

interface ImageUploadProps {
    label: string;
    name: string;
    value: File | string | null;
    onChange: (file: File | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    previewClassName?: string;
}

export function ImageUpload({
    label,
    name,
    value,
    onChange,
    error,
    disabled = false,
    required = false,
    previewClassName = "w-32 h-32"
}: ImageUploadProps) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Atualiza preview quando value muda
    useState(() => {
        if (typeof value === 'string') {
            setPreviewUrl(value);
        } else if (value instanceof File) {
            readFileAsDataURL(value).then(setPreviewUrl);
        } else {
            setPreviewUrl(null);
        }
    });

    const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        const validationError = validateImage(file);
        if (validationError) {
            alert(validationError);
            return;
        }

        setIsLoading(true);
        try {
            // Comprime a imagem antes de enviar
            const compressedFile = await compressImage(file);
            const dataUrl = await readFileAsDataURL(compressedFile);

            setPreviewUrl(dataUrl);
            onChange(compressedFile);
        } catch (error) {
            alert('Erro ao processar imagem. Tente novamente.');
        } finally {
            setIsLoading(false);
            // Limpa o input para permitir selecionar o mesmo arquivo novamente
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleRemoveImage = () => {
        setPreviewUrl(null);
        onChange(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        if (disabled) return;

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            const event = {
                target: { files: [file] }
            } as unknown as React.ChangeEvent<HTMLInputElement>;
            handleFileSelect(event);
        }
    };

    const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
        e.preventDefault();
    };

    return (
        <div className="space-y-3">
            <Label htmlFor={name}>
                {label}
                {required && " *"}
            </Label>

            <div
                className={`border-2 border-dashed border-muted-foreground/25 rounded-lg p-6 text-center transition-colors hover:border-primary/50 ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                    }`}
                onDrop={handleDrop}
                onDragOver={handleDragOver}
                onClick={() => !disabled && fileInputRef.current?.click()}
            >
                <input
                    ref={fileInputRef}
                    id={name}
                    name={name}
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    onChange={handleFileSelect}
                    disabled={disabled || isLoading}
                    className="hidden"
                />

                {previewUrl ? (
                    <div className="flex flex-col items-center space-y-3">
                        <div className={`relative ${previewClassName}`}>
                            <img
                                src={previewUrl}
                                alt="Preview"
                                className="w-full h-full object-cover rounded-lg border"
                            />
                            {!disabled && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    className="absolute -top-2 -right-2 w-6 h-6 rounded-full"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleRemoveImage();
                                    }}
                                    disabled={isLoading}
                                >
                                    <X className="w-3 h-3" />
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Clique para alterar a imagem
                        </p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        <div className="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto">
                            {isLoading ? (
                                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                            ) : (
                                <Upload className="w-6 h-6 text-muted-foreground" />
                            )}
                        </div>
                        <div>
                            <p className="font-medium text-sm">
                                {isLoading ? 'Processando...' : 'Clique para enviar ou arraste uma imagem'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                JPEG, PNG, GIF ou WebP • Máx. 5MB
                            </p>
                        </div>
                    </div>
                )}
            </div>

            {error && (
                <p className="text-red-500 text-sm">{error}</p>
            )}
        </div>
    );
}