// pages/admin/AddCompany.tsx
import { useNavigate } from "react-router-dom";
import { CompanyForm } from "@/components/forms/CompanyForm";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { useState } from "react";

export default function AddCompany() {
    const navigate = useNavigate();
    const { user } = useUser();
    const [isSubmitting, setIsSubmitting] = useState(false);

    if (!user || user.type !== 'admin') {
        navigate('/');
        return null;
    }

    const handleSubmit = async (formData: any) => {
        console.log('Dados do formulário:', formData);
        setIsSubmitting(true);
        try {
            console.log('Enviando para API...');
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

                await api.post("/admin/companies", formDataToSend, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
            } else {
                // Sem imagem, envia JSON normal
                const response = await api.post("/admin/companies", formData);
                console.log('Resposta da API:', response);
            }

            navigate("/admin/companies", {
                state: {
                    message: "Empresa cadastrada com sucesso!",
                    type: "success"
                }
            });
        } catch (error) {
            console.error('Erro ao cadastrar empresa:', error);
            console.error('Resposta do erro:', error.response);

            // Se for erro de validação, relança para o CompanyForm tratar
            if (error.response?.data?.errors) {
                throw error;
            } else {
                alert('Erro ao cadastrar empresa. Tente novamente.');
            }
        } finally {
            setIsSubmitting(false);
        }
        // await api.post("/admin/companies", formData);
    };

    const handleSuccess = () => {
        navigate("/admin/companies", {
            state: { message: "Empresa cadastrada com sucesso!" }
        });
    };

    return (
        <div className="container mx-auto px-4 py-6">
            <CompanyForm
                onSubmit={async (data) => {
                    await handleSubmit(data);
                    handleSuccess();
                }}
                onCancel={() => navigate("/admin/companies")}
                showStatus
                title="Nova Empresa"
                saveButtonText="Cadastrar Empresa"
            />
        </div>
    );
}