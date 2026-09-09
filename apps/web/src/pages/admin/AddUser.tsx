import { useNavigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { useState } from "react";
import { UserForm } from "@/components/forms/UserForm";

export default function AddUser() {
    const navigate = useNavigate();
    const { user } = useUser();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (formData: any) => {
        console.log('Dados do formulário:', formData);
        setIsSubmitting(true);
        try {
            console.log('Enviando para API...');

            const response = await api.post("/admin/users", formData);
            console.log('Resposta da API:', response);

            navigate("/admin/users", {
                state: {
                    message: "Usuário cadastrado com sucesso!",
                    type: "success"
                }
            });
        } catch (error) {
            console.error('Erro ao cadastrar usuário:', error);
            console.error('Resposta do erro:', error.response);

            if (error.response?.data?.errors) {
                throw error;
            } else {
                alert('Erro ao cadastrar usuário. Tente novamente.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleSuccess = () => {
        navigate("/admin/users", {
            state: { message: "Usuário cadastrado com sucesso!" }
        });
    };

    return (
        <div className="container mx-auto px-4 py-6">
            <UserForm
                onSubmit={async (data) => {
                    await handleSubmit(data);
                    handleSuccess();
                }}
                onCancel={() => navigate("/admin/users")}
            />
        </div>
    );
}