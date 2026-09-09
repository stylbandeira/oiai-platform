// components/forms/CompanyForm.tsx
import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FormLayout } from "./FormLayout";
import { FormInput, FormTextarea } from "./FormFields";
import { Building2 } from "lucide-react";
import { ImageUpload } from "./ImageUpload";
import { FormSearchSelect } from "./FormSearchSelect";
import api from "@/lib/api";
import { useUser } from "@/contexts/UserContext";

interface CompanyFormData {
    name: string;
    cnpj: string;
    website: string;
    email: string;
    status: string;
    img?: File | string | null;
    phone?: string;
    description?: string;
    raw_address?: string;
}

const defaultProductData: CompanyFormData = {
    name: "",
    cnpj: "",
    website: "",
    email: "",
    status: "",
    phone: "",
    description: "",
    raw_address: "",
};

interface CompanyFormProps {
    initialData?: CompanyFormData;
    onSubmit: (data: CompanyFormData) => Promise<void>;
    onCancel?: () => void;
    isEditing?: boolean;
    isLoading?: boolean;
};

export function UserCompanyForm({
    initialData = defaultProductData,
    onSubmit,
    onCancel,
    isEditing = false,
    isLoading = false
}: CompanyFormProps) {
    const navigate = useNavigate();
    const { user } = useUser();
    const [formData, setFormData] = useState<CompanyFormData>(initialData);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [categories, setCategories] = useState([]);
    const [unities, setUnities] = useState([]);

    useEffect(() => {
        const loadData = async () => {

            try {
                const [unitiesRes, categoriesRes] = await Promise.all([
                    api.get("/unities"),
                    api.get("/categories")
                ]);

                setUnities(unitiesRes.data.unities);
                setCategories(categoriesRes.data.categories);
            } catch (error) {
                console.error("Erro ao carregar dados:", error);
            } finally {
            }
        };

        loadData();
    }, []);

    const handleInputChange = (
        e: React.ChangeEvent<HTMLInputElement>
    ) => {
        const { name, type, value, checked } = e.target;

        setFormData(prev => ({
            ...prev,
            [name]: type === "checkbox" ? checked : value
        }));
    };

    const handleImageChange = (file: File | null) => {
        setFormData(prev => ({ ...prev, img: file }));
    };

    const handleFormTextArea = (
        e: React.ChangeEvent<HTMLTextAreaElement>
    ) => {
        const { name, value } = e.target;

        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        try {
            await onSubmit({
                ...formData,
                img: formData.img instanceof File ? formData.img : undefined,
            });
        } catch (error: any) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <FormLayout
            title={isEditing ? "Editar Empresa" : "Solicitar cadastro"}
            subtitle={isEditing ? "Atualize os dados da empresa" : "Solicite login da empresa"}
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
                        placeholder="Digite o nome do produto"
                        required
                        disabled={isLoading}
                        error={errors.name?.[0]}
                    />

                    <div className="flex gap-4">
                        <div className="flex-1">
                            <FormInput
                                label="CNPJ"
                                name="cnpj"
                                type="number"
                                value={formData.cnpj}
                                onChange={handleInputChange}
                                required
                                disabled={isLoading}
                                error={errors.cnpj?.[0]}
                            />
                        </div>
                    </div>
                    <FormInput
                        label="Website"
                        name="website"
                        value={formData.website}
                        onChange={handleInputChange}
                        required
                        disabled={isLoading}
                        error={errors.website?.[0]}
                    />

                    <div className="flex gap-4">

                        <FormInput
                            label="Email"
                            name="email"
                            value={formData.email}
                            onChange={handleInputChange}
                            disabled={isLoading}
                            error={errors.email?.[0]}
                        />

                        <FormInput
                            label="Telefone"
                            name="phone"
                            value={formData.phone}
                            onChange={handleInputChange}
                            disabled={isLoading}
                            error={errors.phone?.[0]}
                        />
                    </div>

                    <FormInput
                        label="Endereço completo"
                        name="raw_address"
                        value={formData.raw_address}
                        onChange={handleInputChange}
                        disabled={isLoading}
                        error={errors.raw_address?.[0]}
                    />

                    <FormTextarea
                        label="Descrição"
                        name="description"
                        value={formData.description}
                        disabled={isLoading}
                        onChange={handleFormTextArea}
                        error={errors.description?.[0]}
                    />

                </div>

                {/* Coluna 2 */}
                <div className="space-y-4">

                    <ImageUpload
                        label="Logotipo"
                        name="img"
                        value={formData.img}
                        onChange={handleImageChange}
                        error={errors.img?.[0]}
                        disabled={isLoading}
                        previewClassName="w-48 h-48"
                    />
                </div>
            </div>
        </FormLayout >
    );
}