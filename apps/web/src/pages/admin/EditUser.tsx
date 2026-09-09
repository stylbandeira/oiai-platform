import { useNavigate, useParams } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import api from "@/lib/api";
import { useEffect, useState } from "react";
import { ProductForm } from "@/components/forms/ProductForm";
import { UserForm } from "@/components/forms/UserForm";

export default function EditUser() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { user } = useUser();
    const [editedUser, setEditedUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        fetchUser();
    }, [id]);

    const fetchUser = async () => {
        try {
            const response = await api.get(`/admin/users/${id}`);

            setEditedUser({
                ...response.data.data,
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
            const response = await api.put(`/admin/users/${id}`, formData);

            navigate("/admin/users", {
                state: {
                    message: "Usuário atualizado com sucesso!",
                    type: "success"
                }
            });
        } catch (error) {
            console.error('❌ Erro ao atualizar usuário:', error);

            if (error.response?.data?.errors) {
                throw error;
            } else {
                alert('Erro ao atualizar usuário. Tente novamente.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        navigate('/admin/users');
    }

    if (loading) {
        return (
            <>
                <div className="container mx-auto px-4 py-6">Carregando...</div>
            </>
        );
    }

    return (
        <>
            <div className="container mx-auto px-4 py-6">
                <UserForm
                    initialData={editedUser}
                    onSubmit={handleSubmit}
                    onCancel={handleCancel}
                    isEditing={true}
                    isLoading={isSubmitting}
                />
            </div>
        </>
    );
}