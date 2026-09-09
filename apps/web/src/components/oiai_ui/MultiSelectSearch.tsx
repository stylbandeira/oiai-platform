// components/forms/MultiSelectSearch.tsx
import { useState, useRef, useEffect } from "react";
import { Search, ChevronDown, Plus, Power, PowerOff, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

interface Option {
    id: number;
    name: string;
    email?: string;
    cnpj?: string;
}

type CompanyOwnershipStatus = "active" | "inactive" | "pending";

interface CompanyOwnership {
    id: number;
    status: CompanyOwnershipStatus;
}

interface MultiSelectSearchProps {
    label: string;
    value: CompanyOwnership[];
    onChange: (companies: CompanyOwnership[]) => void;
    options: Option[];
    isLoading?: boolean;
    error?: string;
    placeholder?: string;
    disabled?: boolean;
    onSearch?: (term: string) => void;
}

export function MultiSelectSearch({
    label,
    value,
    onChange,
    options,
    isLoading = false,
    error,
    placeholder = "Buscar e selecionar...",
    disabled = false,
    onSearch
}: MultiSelectSearchProps) {
    const [searchTerm, setSearchTerm] = useState("");
    const [isOpen, setIsOpen] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!onSearch || disabled) return;

        const timeout = window.setTimeout(() => {
            onSearch(searchTerm);
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [searchTerm, onSearch, disabled]);

    const normalizedSearch = searchTerm.toLowerCase();
    const filteredOptions = options.filter(option =>
        option.name.toLowerCase().includes(normalizedSearch) ||
        option.email?.toLowerCase().includes(normalizedSearch) ||
        option.cnpj?.includes(searchTerm)
    ).slice(0, 8);

    const selectedOptions = value
        .map(company => ({
            ...company,
            option: options.find(option => option.id === company.id),
        }))
        .filter(company => company.option);

    const selectedIds = value.map(company => company.id);
    const statusLabels: Record<CompanyOwnershipStatus, string> = {
        active: "Ativa",
        inactive: "Inativa",
        pending: "Pendente",
    };

    const statusVariants: Record<CompanyOwnershipStatus, "default" | "secondary" | "outline"> = {
        active: "default",
        inactive: "secondary",
        pending: "outline",
    };

    const handleSelect = (optionId: number) => {
        if (selectedIds.includes(optionId)) {
            onChange(value.map(company =>
                company.id === optionId ? { ...company, status: "active" } : company
            ));
        } else {
            onChange([...value, { id: optionId, status: "active" }]);
        }
        setSearchTerm("");
        setIsOpen(false);
    };

    const handleStatusChange = (optionId: number, status: CompanyOwnershipStatus) => {
        onChange(value.map(company =>
            company.id === optionId ? { ...company, status } : company
        ));
    };

    const handleRemove = (optionId: number) => {
        onChange(value.filter(company => company.id !== optionId));
    };

    const handleInputFocus = () => {
        setIsOpen(true);
    };

    const handleInputBlur = (e: React.FocusEvent) => {
        // Delay para permitir clicar nas opções
        setTimeout(() => setIsOpen(false), 200);
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && searchTerm && filteredOptions.length > 0) {
            e.preventDefault();
            handleSelect(filteredOptions[0].id);
        }
        if (e.key === 'Backspace' && !searchTerm && value.length > 0) {
            handleStatusChange(value[value.length - 1].id, "inactive");
        }
    };

    return (
        <div className="space-y-2">
            <label className="text-sm font-medium leading-none">
                {label}
            </label>

            <div className="rounded-md border bg-background">
                {selectedOptions.length === 0 ? (
                    <div className="px-3 py-3 text-sm text-muted-foreground">
                        Nenhuma empresa vinculada
                    </div>
                ) : (
                    <div className="divide-y">
                        {selectedOptions.map(({ id, status, option }) => (
                            <div key={id} className="flex items-center justify-between gap-3 px-3 py-2">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="truncate text-sm font-medium">{option.name}</p>
                                        <Badge variant={statusVariants[status]} className="text-xs">
                                            {statusLabels[status]}
                                        </Badge>
                                    </div>
                                    {(option.email || option.cnpj) && (
                                        <p className="truncate text-xs text-muted-foreground">
                                            {[option.email, option.cnpj].filter(Boolean).join(" • ")}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleStatusChange(id, status === "active" ? "inactive" : "active")}
                                        disabled={disabled}
                                        aria-label={`${status === "active" ? "Desativar" : "Ativar"} ${option.name}`}
                                    >
                                        {status === "active" ? (
                                            <PowerOff className="h-4 w-4" />
                                        ) : (
                                            <Power className="h-4 w-4" />
                                        )}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => handleRemove(id)}
                                        disabled={disabled}
                                        aria-label={`Remover vínculo com ${option.name}`}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div className="relative">
                <div className="flex items-center gap-2 border rounded-md p-2 min-h-10 bg-background">
                    <Search className="w-4 h-4 text-muted-foreground" />
                    <div className="flex flex-1">
                        <input
                            ref={inputRef}
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            onFocus={handleInputFocus}
                            onBlur={handleInputBlur}
                            onKeyDown={handleKeyDown}
                            placeholder={placeholder}
                            disabled={disabled}
                            className="flex-1 outline-none bg-transparent min-w-20"
                        />
                    </div>

                    <ChevronDown className="w-4 h-4 text-muted-foreground" />
                </div>

                {/* Dropdown de opções */}
                {isOpen && !disabled && (
                    <div className="absolute z-50 w-full mt-1 bg-popover border rounded-md shadow-lg max-h-60 overflow-auto">
                        {isLoading ? (
                            <div className="p-2 text-sm text-muted-foreground">Carregando...</div>
                        ) : filteredOptions.length === 0 ? (
                            <div className="p-2 text-sm text-muted-foreground">
                                {searchTerm ? "Nenhum resultado encontrado" : "Digite para buscar"}
                            </div>
                        ) : (
                            filteredOptions.map(option => (
                                <div
                                    key={option.id}
                                    className={`p-2 cursor-pointer hover:bg-accent ${selectedIds.includes(option.id) ? 'bg-accent' : ''
                                        }`}
                                    onClick={() => handleSelect(option.id)}
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">{option.name}</p>
                                            {(option.email || option.cnpj) && (
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {[option.email, option.cnpj].filter(Boolean).join(" • ")}
                                                </p>
                                            )}
                                        </div>
                                        {selectedIds.includes(option.id) ? (
                                            <Badge variant="outline" className="text-xs">Selecionada</Badge>
                                        ) : (
                                            <Plus className="h-4 w-4 text-muted-foreground" />
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                )}
            </div>

            {error && (
                <p className="text-sm text-destructive">{error}</p>
            )}
        </div>
    );
}
