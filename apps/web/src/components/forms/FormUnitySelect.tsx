// components/forms/FormUnitySelect.tsx
import { useState, useEffect } from "react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Check, ChevronDown, Loader2 } from "lucide-react";
import api from "@/lib/api";

interface Unity {
    id: number;
    name: string;
    abbreviation: string;
}

interface FormUnitySelectProps {
    label: string;
    name: string;
    value: string;
    onValueChange: (value: string) => void;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
}

export function FormUnitySelect({
    label,
    name,
    value,
    onValueChange,
    placeholder = "Selecione a unidade",
    required = false,
    disabled = false,
    error
}: FormUnitySelectProps) {
    const [unities, setUnities] = useState<Unity[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    const [isSearching, setIsSearching] = useState(false);

    useEffect(() => {
        fetchUnities();
    }, []);

    const fetchUnities = async () => {
        setIsLoading(true);
        try {
            const response = await api.get("/unities");
            setUnities(response.data.unities);
        } catch (error) {
            console.error("Erro ao buscar unidades:", error);
        } finally {
            setIsLoading(false);
        }
    };

    const filteredUnities = unities.filter(unity =>
        unity.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        unity.abbreviation.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleSelectUnity = (unity: Unity) => {
        onValueChange(unity.id.toString()); // ← MUDANÇA AQUI
        setSearchTerm("");
        setIsOpen(false);
        setIsSearching(false);
    };

    const selectedUnity = unities.find(unity => unity.id.toString() === value);

    // Texto para exibir
    const displayText = isSearching ? searchTerm :
        selectedUnity ? `${selectedUnity.name} (${selectedUnity.abbreviation})` :
            value;

    const handleInputClick = () => {
        if (!disabled) {
            setIsOpen(true);
            setIsSearching(true);
            setSearchTerm("");
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchTerm(e.target.value);
        setIsOpen(true);
        setIsSearching(true);
    };

    const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
        // Pequeno delay para permitir a seleção de um item antes de fechar
        setTimeout(() => {
            setIsOpen(false);
            setIsSearching(false);
            setSearchTerm(""); // Limpa a busca ao fechar
        }, 200);
    };

    return (
        <div className="space-y-2 relative">
            <Label htmlFor={name}>{label}{required && " *"}</Label>

            <div className="relative">
                <Input
                    value={displayText}
                    onChange={handleInputChange}
                    onClick={handleInputClick}
                    onBlur={handleBlur}
                    placeholder={placeholder}
                    required={required}
                    disabled={disabled}
                    className="pr-10 cursor-pointer"
                />
                <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            </div>

            {/* Dropdown */}
            {isOpen && !disabled && (
                <>
                    <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto">
                        {isLoading ? (
                            <div className="flex items-center justify-center p-4">
                                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                <span>Carregando unidades...</span>
                            </div>
                        ) : filteredUnities.length === 0 ? (
                            <div className="p-4 text-center text-muted-foreground">
                                Nenhuma unidade encontrada
                            </div>
                        ) : (
                            filteredUnities.map((unity) => {
                                const displayValue = `${unity.name} (${unity.abbreviation})`;
                                const isSelected = value === displayValue;

                                return (
                                    <div
                                        key={unity.id}
                                        onMouseDown={(e) => e.preventDefault()}
                                        onClick={() => handleSelectUnity(unity)}
                                        className={`p-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0 flex items-center justify-between ${isSelected ? "bg-blue-50" : ""
                                            }`}
                                    >
                                        <div>
                                            <div className="font-medium">{unity.name}</div>
                                            <div className="text-sm text-muted-foreground">{unity.abbreviation}</div>
                                        </div>
                                        {isSelected && <Check className="h-4 w-4 text-blue-600" />}
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Overlay para fechar ao clicar fora */}
                    <div
                        className="fixed inset-0 z-40"
                        onClick={() => setIsOpen(false)}
                    />
                </>
            )}

            {error && <p className="text-red-500 text-sm">{error}</p>}
        </div>
    );
}