import { useState } from "react";
import { AlertTriangle, Building2, Check, ChevronDown } from "lucide-react";
import { Company } from "@/types/company";

interface CompanySelectorProps {
    companies: Company[];
    selectedCompanyId: number | null;
    onChange: (companyId: number) => void;
}

export function CompanySelector({
    companies,
    selectedCompanyId,
    onChange,
}: CompanySelectorProps) {
    const [isOpen, setIsOpen] = useState(false);

    const selectedCompany = companies.find(
        (company) => company.id === selectedCompanyId
    );

    if (!companies || companies.length === 0) {
        return (
            <div className="mb-6 flex max-w-md items-center gap-3 rounded-2xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                <AlertTriangle className="h-5 w-5 flex-shrink-0" />

                <div>
                    <p className="font-semibold">Nenhuma empresa disponível</p>
                    <p className="text-xs opacity-80">
                        Solicite acesso a uma empresa para continuar.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="relative mb-6 w-full max-w-xs">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between gap-3 rounded-2xl border bg-background px-4 py-3 text-sm shadow-sm transition hover:bg-muted/40"
            >
                <div className="flex min-w-0 items-center gap-3">
                    <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-primary/10">
                        <Building2 className="h-4 w-4 text-primary" />
                    </div>

                    <div className="min-w-0 text-left">
                        <p className="text-xs text-muted-foreground">
                            Empresa selecionada
                        </p>
                        <p className="truncate font-medium">
                            {selectedCompany?.name ?? "Selecionar empresa"}
                        </p>
                    </div>
                </div>

                <ChevronDown
                    className={`h-4 w-4 text-muted-foreground transition-transform ${isOpen ? "rotate-180" : ""
                        }`}
                />
            </button>

            {isOpen && (
                <div className="absolute left-0 top-full z-50 mt-2 w-full overflow-hidden rounded-2xl border bg-background shadow-lg">
                    <div className="max-h-72 overflow-y-auto p-2">
                        {companies.map((company) => {
                            const isSelected = company.id === selectedCompanyId;

                            return (
                                <button
                                    key={company.id}
                                    type="button"
                                    onClick={() => {
                                        onChange(company.id);
                                        setIsOpen(false);
                                    }}
                                    className={`
                                        flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition
                                        ${isSelected
                                            ? "bg-primary/10 text-primary"
                                            : "hover:bg-muted"
                                        }
                                    `}
                                >
                                    <span className="truncate">
                                        {company.name}
                                    </span>

                                    {isSelected && (
                                        <Check className="h-4 w-4 flex-shrink-0" />
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}