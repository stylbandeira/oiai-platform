
import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FormLayout } from "./FormLayout";
import { FormInput } from "./FormFields";
import { Building2 } from "lucide-react";
import { ImageUpload } from "./ImageUpload";
import { FormSearchSelect } from "./FormSearchSelect";
import api from "@/lib/api";
import { useUser } from "@/contexts/UserContext";

interface ProductFormData {
    name: string;
    quantity: string;
    unity: string;
    sku: string;
    average_price: string;
    category: string;
    img?: File | string | null;
    unit_id?: string;
    category_id?: string;
    ean?: string;
    validated?: boolean;
}

interface ProductFormProps {
    initialData?: ProductFormData;
    onSubmit: (data: ProductFormData) => Promise<void>;
    onCancel?: () => void;
    isEditing?: boolean;
    isLoading?: boolean;
}

const defaultProductData: ProductFormData = {
    name: "",
    quantity: "",
    unity: "",
    sku: "",
    average_price: "",
    category: "",
    unit_id: "",
    category_id: "",
    ean: ""
};

export function ProductForm({
    initialData = defaultProductData,
    onSubmit,
    onCancel,
    isEditing = false,
    isLoading = false
}: ProductFormProps) {
    const navigate = useNavigate();
    const { user } = useUser();
    const [formData, setFormData] = useState<ProductFormData>(initialData);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isLoadingUnities, setIsLoadingUnities] = useState(false);
    const [isLoadingCategories, setIsLoadingCategories] = useState(false);
    const [categories, setCategories] = useState([]);
    const [unities, setUnities] = useState([]);

    useEffect(() => {
        const loadData = async () => {
            setIsLoadingUnities(true);
            setIsLoadingCategories(true);

            try {
                const [unitiesRes, categoriesRes] = await Promise.all([
                    api.get("/unities"),
                    api.get("/categories")
                ]);

                setUnities(unitiesRes.data.data);
                setCategories(categoriesRes.data.data);
            } catch (error) {
                console.error("Erro ao carregar dados:", error);
            } finally {
                setIsLoadingUnities(false);
                setIsLoadingCategories(false);
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

    const handleCategoryChange = (id) => {
        setFormData(prev => ({ ...prev, category_id: id }));
        setFormData(prev => ({ ...prev, category: id }));
    }

    const handleUnityChange = (id) => {
        setFormData(prev => ({ ...prev, unit_id: id }));
        setFormData(prev => ({ ...prev, unity: id }));
    }

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
            title={isEditing ? "Editar Produto" : "Novo Produto"}
            subtitle={isEditing ? "Atualize os dados da produto" : "Cadastre uma novo produto no sistema"}
            icon={<Building2 className="w-6 h-6" />}
            onSave={handleSubmit}
            onCancel={onCancel}
            isLoading={isLoading}
            saveButtonText={isEditing ? "Atualizar Produto" : "Cadastrar Produto"}
        >
            {errors.general && (
                <div className="p-3 bg-destructive/10 border border-destructive rounded-md">
                    <p className="text-destructive text-sm">{errors.general[0]}</p>
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Coluna 1 */}
                <div className="space-y-4">
                    <FormSearchSelect
                        label="Categoria"
                        name="category"
                        value={formData.category}
                        onValueChange={handleCategoryChange}
                        placeholder="Selecione a categoria"
                        required
                        disabled={isLoading}
                        error={errors.category?.[0]}
                        options={categories}
                        isLoading={isLoadingCategories}
                    />

                    <FormInput
                        label="Nome do Produto"
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
                                label="Quantidade"
                                name="quantity"
                                type="number"
                                value={formData.quantity}
                                onChange={handleInputChange}
                                required
                                disabled={isLoading}
                                error={errors.quantity?.[0]}
                            />
                        </div>
                        <div className="flex-1">

                            <FormSearchSelect
                                label="Unidade"
                                name="unity"
                                value={formData.unity}
                                onValueChange={handleUnityChange}
                                placeholder="Selecione a unidade"
                                required
                                disabled={isLoading}
                                error={errors.unity?.[0]}
                                options={unities}
                                isLoading={isLoadingUnities}
                            />
                        </div>
                    </div>

                    <div className="flex gap-4">
                        <FormInput
                            label="SKU"
                            name="sku"
                            value={formData.sku}
                            onChange={handleInputChange}
                            required
                            disabled={isLoading}
                            error={errors.sku?.[0]}
                        />
                        <FormInput
                            label="EAN"
                            name="ean"
                            type="number"
                            value={formData.ean}
                            onChange={handleInputChange}
                            disabled={isLoading}
                            error={errors.ean?.[0]}
                        />
                    </div>

                    <div className="flex-1">
                        <FormInput
                            label="Preço"
                            name="average_price"
                            type="number"
                            value={formData.average_price}
                            onChange={handleInputChange}
                            required
                            disabled={isLoading}
                            error={errors.average_price?.[0]}
                        />
                    </div>

                    {user.type === 'admin' && (
                        <div className="flex gap-4">
                            <FormInput
                                label="Produto Validado"
                                type="checkbox"
                                name="validated"
                                checked={formData.validated}
                                onChange={handleInputChange}
                                disabled={isLoading}
                                error={errors.validated?.[0]}
                            />
                        </div>
                    )}

                </div>

                {/* Coluna 2 */}
                <div className="space-y-4">

                    <ImageUpload
                        label="Imagem do Produto"
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