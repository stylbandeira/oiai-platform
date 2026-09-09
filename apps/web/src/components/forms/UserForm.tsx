import { useCallback, useEffect, useMemo, useState } from "react";
import { FormLayout } from "./FormLayout";
import { FormInput, FormSelect } from "./FormFields";
import { Building2 } from "lucide-react";
import api from "@/lib/api";
import { MultiSelectSearch } from "../oiai_ui/MultiSelectSearch";
import type { AxiosError } from "axios";

interface CompanyOption {
    id: number;
    name: string;
    email?: string;
    cnpj?: string;
    ownership_status?: CompanyOwnershipStatus;
    status?: string;
    pivot?: {
        status?: string;
        ownership_status?: string;
    };
}

type CompanyOwnershipStatus = "active" | "inactive" | "pending";

interface CompanyOwnership {
    id: number;
    status: CompanyOwnershipStatus;
}

interface UserFormData {
    name: string;
    type: "client" | "admin" | "company";
    email: string;
    cpf: string;
    status: "active" | "inactive" | "suspended";
    companies: number[] | CompanyOption[] | CompanyOwnership[];
}

interface UserFormProps {
    initialData?: UserFormData;
    onSubmit: (data: UserFormData) => Promise<void>;
    onCancel?: () => void;
    isEditing?: boolean;
    isLoading?: boolean;
}

interface ValidationErrorResponse {
    errors?: Record<string, string[]>;
}

const isCompanyOwnershipStatus = (status?: string): status is CompanyOwnershipStatus =>
    status === "active" || status === "inactive" || status === "pending";

const getCompanyOwnershipStatus = (company: CompanyOption | CompanyOwnership): CompanyOwnershipStatus => {
    if ("ownership_status" in company && isCompanyOwnershipStatus(company.ownership_status)) {
        return company.ownership_status;
    }

    if ("pivot" in company) {
        if (isCompanyOwnershipStatus(company.pivot?.ownership_status)) {
            return company.pivot.ownership_status;
        }

        if (isCompanyOwnershipStatus(company.pivot?.status)) {
            return company.pivot.status;
        }
    }

    if (!("name" in company) && isCompanyOwnershipStatus(company.status)) {
        return company.status;
    }

    return "active";
};

const normalizeUserData = (data: UserFormData): UserFormData => ({
    ...data,
    companies: Array.isArray(data.companies)
        ? data.companies.map(company => {
            if (typeof company === "number") {
                return { id: company, status: "active" };
            }

            return {
                id: company.id,
                status: getCompanyOwnershipStatus(company),
            };
        })
        : [],
});

const defaultUserData: UserFormData = {
    name: "",
    type: "client",
    email: "",
    cpf: "",
    status: "active",
    companies: [],
};

export function UserForm({
    initialData = defaultUserData,
    onSubmit,
    onCancel,
    isEditing = false,
    isLoading = false
}: UserFormProps) {
    const [formData, setFormData] = useState<UserFormData>(() => normalizeUserData(initialData));
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLoadingCompanies, setIsLoadingCompanies] = useState(false);

    const statusOptions = [
        { value: "active", label: "Ativo" },
        { value: "inactive", label: "Inativo" },
        { value: "suspended", label: "Suspenso" },
    ];

    const typeOptions = [
        { value: "client", label: "Usuário Comum" },
        { value: "company", label: "Empresa" },
        { value: "admin", label: "Administrador" },
    ];

    const [companies, setCompanies] = useState<CompanyOption[]>([]);

    const isCompanyType = formData.type === 'company';

    const initialCompanyOptions = useMemo(() => {
        if (!Array.isArray(initialData.companies)) return [];

        return initialData.companies.filter(
            (company): company is CompanyOption => typeof company !== "number"
        );
    }, [initialData.companies]);

    const initialComparableData = useMemo(() => {
        const normalized = normalizeUserData(initialData);

        return {
            ...normalized,
            companies: normalized.type === "company"
                ? [...(normalized.companies as CompanyOwnership[])].sort((a, b) => a.id - b.id)
                : [],
        };
    }, [initialData]);

    const comparableFormData = useMemo(() => ({
        ...formData,
        companies: isCompanyType ? [...(formData.companies as CompanyOwnership[])].sort((a, b) => a.id - b.id) : [],
    }), [formData, isCompanyType]);

    const hasChanges = !isEditing || JSON.stringify(comparableFormData) !== JSON.stringify(initialComparableData);
    const formDisabled = isLoading || isSubmitting;

    const mergeCompanies = useCallback((loadedCompanies: CompanyOption[]) => {
        setCompanies(prev => {
            const companyMap = new Map<number, CompanyOption>();

            [...initialCompanyOptions, ...prev, ...loadedCompanies].forEach(company => {
                companyMap.set(company.id, company);
            });

            return Array.from(companyMap.values());
        });
    }, [initialCompanyOptions]);

    const loadCompanies = useCallback(async (search: string = "") => {
        setIsLoadingCompanies(true);

        try {
            const response = await api.get("/admin/companies", {
                params: search ? { search } : undefined,
            });

            mergeCompanies(response.data.data ?? []);
        } catch (error) {
            console.error("Erro ao carregar empresas:", error);
        } finally {
            setIsLoadingCompanies(false);
        };
    }, [mergeCompanies]);

    useEffect(() => {
        setFormData(normalizeUserData(initialData));
        mergeCompanies(initialCompanyOptions);
    }, [initialData, initialCompanyOptions, mergeCompanies]);

    useEffect(() => {
        loadCompanies();
    }, [loadCompanies]);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleCompaniesChange = (companyOwnerships: CompanyOwnership[]) => {
        setFormData(prev => ({ ...prev, companies: companyOwnerships }));
    };

    const handleTypeChange = (type: string) => {
        const newType = type as "client" | "admin" | "company";

        setFormData(prev => ({
            ...prev,
            type: newType,
            companies: newType === 'company' ? prev.companies : []
        }));
    };

    const handleStatusChange = (status: string) => {
        setFormData(prev => ({
            ...prev,
            status: status as "active" | "inactive" | "suspended"
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        try {
            const submitData = { ...formData, companies: formData.companies as CompanyOwnership[] };
            if (!isCompanyType) {
                submitData.companies = [];
            }

            await onSubmit(submitData);
        } catch (error: unknown) {
            const axiosError = error as AxiosError<ValidationErrorResponse>;

            if (axiosError.response?.data?.errors) {
                setErrors(axiosError.response.data.errors);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <FormLayout
            title={isEditing ? "Editar Usuário" : "Novo Usuário"}
            subtitle={isEditing ? "Atualize os dados da usuário" : "Cadastre uma novo usuário no sistema"}
            icon={<Building2 className="w-6 h-6" />}
            onSave={handleSubmit}
            onCancel={onCancel}
            isLoading={formDisabled}
            saveButtonText={isEditing ? "Atualizar Usuário" : "Cadastrar Usuário"}
            disableSave={isEditing && !hasChanges}
        >
            {errors.general && (
                <div className="p-3 bg-destructive/10 border border-destructive rounded-md">
                    <p className="text-destructive text-sm">{errors.general[0]}</p>
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Coluna 1 */}
                <div className="space-y-4">

                    <FormInput
                        label="Nome do Usuário"
                        name="name"
                        value={formData.name}
                        onChange={handleInputChange}
                        placeholder="Digite o nome do produto"
                        required
                        disabled={formDisabled}
                        error={errors.name?.[0]}
                    />

                    <div className="flex gap-4">
                        <div className="flex-1">
                            <FormInput
                                label="Email"
                                name="email"
                                type="email"
                                value={formData.email}
                                onChange={handleInputChange}
                                required
                                disabled={formDisabled}
                                error={errors.email?.[0]}
                            />
                        </div>
                    </div>

                    <FormInput
                        label="CPF"
                        name="cpf"
                        value={formData.cpf}
                        onChange={handleInputChange}
                        required
                        disabled={formDisabled}
                        error={errors.cpf?.[0]}
                    />
                </div>

                {/* Coluna 2 */}
                <div className="space-y-4">

                    <FormSelect
                        label="Tipo de Usuário"
                        name="type"
                        value={formData.type}
                        onValueChange={handleTypeChange}
                        options={typeOptions}
                        placeholder="Selecione o tipo"
                        required
                        disabled={formDisabled}
                        error={errors.type?.[0]}
                    />

                    <FormSelect
                        label="Status"
                        name="status"
                        value={formData.status}
                        onValueChange={handleStatusChange}
                        options={statusOptions}
                        placeholder="Selecione o status"
                        required
                        disabled={formDisabled}
                        error={errors.status?.[0]}
                    />

                    {isCompanyType && (
                        <MultiSelectSearch
                            label="Empresas"
                            value={formData.companies as CompanyOwnership[]}
                            onSearch={loadCompanies}
                            onChange={handleCompaniesChange}
                            options={companies}
                            isLoading={isLoadingCompanies}
                            error={errors.companies?.[0]}
                            placeholder="Buscar empresa para adicionar..."
                            disabled={formDisabled}
                        />
                    )}
                </div>
            </div>
        </FormLayout >
    );
}
