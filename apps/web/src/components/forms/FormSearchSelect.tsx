import { useState, useEffect } from "react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Check, ChevronDown, Loader2 } from "lucide-react";

interface SearchSelectOption {
    id: number | string;
    name: string;
    abbreviation?: string;
}

interface FormSearchSelectProps {
    label: string;
    name: string;
    value: string;
    onValueChange: (value: string) => void;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    displayFormat?: (option: SearchSelectOption) => string;
    options: SearchSelectOption[];
    isLoading?: boolean;
}

export function FormSearchSelect({
    label,
    name,
    value,
    onValueChange,
    placeholder = "Selecione uma opção",
    required = false,
    disabled = false,
    error,
    options,
    isLoading,
    displayFormat = (option) => option.abbreviation ? `${option.name} (${option.abbreviation})` : option.name
}: FormSearchSelectProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    const [isSearching, setIsSearching] = useState(false);

    const filteredOptions = options.filter(option =>
        option.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (option.abbreviation && option.abbreviation.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const handleSelectOption = (option: SearchSelectOption) => {
        onValueChange(option.id.toString());
        setSearchTerm("");
        setIsOpen(false);
        setIsSearching(false);
    };

    const selectedOption = options.find(option => option.id.toString() === value);
    const displayText = isSearching ? searchTerm : (selectedOption ? displayFormat(selectedOption) : value);

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

    const handleBlur = () => {
        setTimeout(() => {
            setIsOpen(false);
            setIsSearching(false);
            setSearchTerm("");
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

            {isOpen && !disabled && (
                <>
                    <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-auto">
                        {isLoading ? (
                            <div className="flex items-center justify-center p-4">
                                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                <span>Carregando...</span>
                            </div>
                        ) : filteredOptions.length === 0 ? (
                            <div className="p-4 text-center text-muted-foreground">
                                Nenhuma opção encontrada
                            </div>
                        ) : (
                            filteredOptions.map((option) => {
                                const isSelected = value === option.id.toString();
                                return (
                                    <div
                                        key={option.id}
                                        onMouseDown={(e) => e.preventDefault()}
                                        onClick={() => handleSelectOption(option)}
                                        className={`p-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 last:border-b-0 flex items-center justify-between ${isSelected ? "bg-blue-50" : ""}`}
                                    >
                                        <div>
                                            <div className="font-medium">{option.name}</div>
                                            {option.abbreviation && (
                                                <div className="text-sm text-muted-foreground">{option.abbreviation}</div>
                                            )}
                                        </div>
                                        {isSelected && <Check className="h-4 w-4 text-blue-600" />}
                                    </div>
                                );
                            })
                        )}
                    </div>
                    <div className="fixed inset-0 z-40" onClick={() => setIsOpen(false)} />
                </>
            )}
            {error && <p className="text-red-500 text-sm">{error}</p>}
        </div>
    );
}