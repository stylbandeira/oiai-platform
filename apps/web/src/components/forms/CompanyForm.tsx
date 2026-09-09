// components/forms/CompanyForm.tsx
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { FormLayout } from "./FormLayout";
import { FormInput, FormTextarea, FormSelect } from "./FormFields";
import { Building2, Globe, MapPin, Mail, Phone, FileText } from "lucide-react";
import { formatCNPJ, removeFormatting } from "@/utils/formatters";
import { ImageUpload } from "./ImageUpload";

interface CompanyFormData {
    name: string;
    email: string;
    cnpj: string;
    website: string;
    raw_address: string;
    phone: string;
    description: string;
    status: "active" | "inactive" | "pending";
    img?: File | string | null;
}

interface CompanyFormProps {
    initialData?: CompanyFormData;
    onSubmit: (data: CompanyFormData) => Promise<void>;
    onCancel?: () => void;
    isEditing?: boolean;
    isLoading?: boolean;
    showStatus?: boolean;
    title?: string;
    subtitle?: string;
    saveButtonText?: string;
}

const defaultCompanyData: CompanyFormData = {
    name: "",
    email: "",
    cnpj: "",
    website: "",
    raw_address: "",
    phone: "",
    description: "",
    status: "active"
};

export function CompanyForm({
    initialData = defaultCompanyData,
    onSubmit,
    onCancel,
    isEditing = false,
    isLoading = false,
    showStatus = true,
}: CompanyFormProps) {
    const navigate = useNavigate();
    const [formData, setFormData] = useState<CompanyFormData>(initialData);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleImageChange = (file: File | null) => {
        setFormData(prev => ({ ...prev, img: file }));
    };

    const handleSelectChange = (name: string, value: string) => {
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleCNPJChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const formattedCNPJ = formatCNPJ(e.target.value);
        setFormData(prev => ({ ...prev, cnpj: formattedCNPJ }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        try {
            await onSubmit({
                ...formData,
                cnpj: removeFormatting(formData.cnpj),
                img: formData.img instanceof File ? formData.img : undefined
            });
        } catch (error: any) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const statusOptions = [
        { value: "active", label: "Ativo" },
        { value: "inactive", label: "Inativo" },
        { value: "pending", label: "Pendente" }
    ];

    return (
        <FormLayout
            title={isEditing ? "Editar Empresa" : "Nova Empresa"}
            subtitle={isEditing ? "Atualize os dados da empresa" : "Cadastre uma nova empresa no sistema"}
            icon={<Building2 className="w-6 h-6" />}
            onSave={handleSubmit}
            onCancel={onCancel}
            isLoading={isLoading}
            saveButtonText={isEditing ? "Atualizar Empresa" : "Cadastrar Empresa"}
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
                        label="Nome da Empresa"
                        name="name"
                        value={formData.name}
                        onChange={handleInputChange}
                        placeholder="Digite o nome da empresa"
                        required
                        disabled={isLoading}
                        error={errors.name?.[0]}
                    />

                    <FormInput
                        label="Email"
                        name="email"
                        type="email"
                        value={formData.email}
                        onChange={handleInputChange}
                        placeholder="empresa@email.com"
                        required
                        disabled={isLoading}
                        error={errors.email?.[0]}
                        icon={<Mail className="w-4 h-4" />}
                    />

                    <FormInput
                        label="CNPJ"
                        name="cnpj"
                        value={formData.cnpj}
                        onChange={handleCNPJChange}
                        placeholder="00.000.000/0000-00"
                        required
                        disabled={isLoading}
                        error={errors.cnpj?.[0]}
                    />

                    <FormInput
                        label="Telefone"
                        name="phone"
                        value={formData.phone}
                        onChange={handleInputChange}
                        placeholder="(11) 99999-9999"
                        disabled={isLoading}
                        error={errors.phone?.[0]}
                        icon={<Phone className="w-4 h-4" />}
                    />
                </div>

                {/* Coluna 2 */}
                <div className="space-y-4">
                    <FormInput
                        label="Website"
                        name="website"
                        value={formData.website}
                        onChange={handleInputChange}
                        placeholder="https://empresa.com.br"
                        disabled={isLoading}
                        error={errors.website?.[0]}
                        icon={<Globe className="w-4 h-4" />}
                    />

                    {showStatus && (
                        <FormSelect
                            label="Status"
                            name="status"
                            value={formData.status}
                            onValueChange={(value) => handleSelectChange("status", value)}
                            options={statusOptions}
                            required
                            disabled={isLoading}
                            error={errors.status?.[0]}
                        />
                    )}

                    <ImageUpload
                        label="Imagem da Empresa"
                        name="img"
                        value={formData.img}
                        onChange={handleImageChange}
                        error={errors.img?.[0]}
                        disabled={isLoading}
                        previewClassName="w-48 h-48"
                    />
                </div>
            </div>

            <FormTextarea
                label="Endereço"
                name="raw_address"
                value={formData.raw_address}
                onChange={handleInputChange}
                placeholder="Av. Paulista, 1000 - São Paulo, SP"
                disabled={isLoading}
                error={errors.raw_address?.[0]}
                icon={<MapPin className="w-4 h-4" />}
                rows={3}
            />

            <FormTextarea
                label="Descrição"
                name="description"
                value={formData.description}
                onChange={handleInputChange}
                placeholder="Descreva a empresa e seus serviços..."
                disabled={isLoading}
                error={errors.description?.[0]}
                icon={<FileText className="w-4 h-4" />}
                rows={4}
            />
        </FormLayout>
    );
}