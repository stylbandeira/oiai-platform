import { useNavigate, useParams } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { useEffect, useState } from "react";
import { formatCNPJ } from "@/utils/formatters";
import { getFullImageUrl } from "@/utils/urlUtils";
import { ProductForm } from "@/components/forms/ProductForm";

export default function EditProduct() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { user } = useUser();
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        fetchProduct();
    }, [id]);

    const fetchProduct = async () => {
        try {
            const response = await api.get(`/admin/products/${id}`);

            const productImg = getFullImageUrl(response.data.data.img);

            setProduct({
                ...response.data.data,
                img: productImg,
            });

        } catch (error) {
            console.error('Erro ao carregar produto:', error);
        } finally {
            setLoading(false);
        }
    };

    if (!user || user.type !== 'admin') {
        navigate('/');
        return null;
    }

    const handleSubmit = async (formData: any) => {
        setIsSubmitting(true);

        try {
            if (formData.img instanceof File) {
                const formDataToSend = new FormData();

                Object.entries(formData).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        if (value instanceof File) {
                            formDataToSend.append(key, value);
                        } else {
                            formDataToSend.append(key, value.toString());
                        }
                    }
                });

                formDataToSend.append('_method', 'PUT');

                const response = await api.post(`/admin/products/${id}`, formDataToSend, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

            } else {
                const response = await api.put(`/admin/products/${id}`, formData);
            }

            navigate("/admin/products", {
                state: {
                    message: "Produto atualizado com sucesso!",
                    type: "success"
                }
            });
        } catch (error) {
            console.error('❌ Erro ao atualizar produto:', error);

            if (error.response?.data?.errors) {
                throw error;
            } else {
                alert('Erro ao atualizar produto. Tente novamente.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        navigate('/admin/products');
    }

    if (loading) {
        return (
            <>
                <div className="container mx-auto px-4 py-6">Carregando...</div>
            </>
        );
    }

    return (
        <div className="container mx-auto px-4 py-6">
            <ProductForm
                initialData={product}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
                isEditing={true}
                isLoading={isSubmitting}
            />
        </div>
    );
}