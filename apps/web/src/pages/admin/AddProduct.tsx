import { useNavigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { useState } from "react";
import { ProductForm } from "@/components/forms/ProductForm";
import { useNotification } from "@/contexts/NotificationContext";


export default function AddProduct() {
    const navigate = useNavigate();
    const { user } = useUser();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { showNotification } = useNotification();
    const [formKey, setFormKey] = useState(0);

    const handleSubmit = async (formData: any) => {
        console.log('Dados do formulário:', formData);
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

                await api.post("/products", formDataToSend, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
            } else {
                const response = await api.post("/products", formData);
                console.log('Resposta da API:', response);
            }

            showNotification('Produto cadastrado com sucesso', 'success');

            setFormKey(prev => prev + 1);

            navigate(user.type === 'admin' ? "/admin/products/new" : "/products/new", {
                replace: true
            });

            console.log('After navigation');
        } catch (error) {

            if (error.response?.data?.errors) {
                throw error;
            } else {
                alert('Erro ao cadastrar produto. Tente novamente.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSuccess = () => {
        showNotification(
            "Produto cadastrado com sucesso!",
            "success",
            5000 // duração em ms
        );
    };

    return (
        <div className="container mx-auto px-4 py-6">
            <ProductForm
                key={formKey}
                onSubmit={async (data) => {
                    await handleSubmit(data);
                    handleSuccess();
                }}
                onCancel={() => navigate("/admin/products")}
            />
        </div>
    );
}