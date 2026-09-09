// components/ProtectedRoute.tsx
import { Navigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";

interface ProtectedRouteProps {
    children: React.ReactNode;
    allowedTypes?: string[];
    redirectTo?: string;
}

export const ProtectedRoute = ({
    children,
    allowedTypes = ["admin"],
    redirectTo = "/"
}: ProtectedRouteProps) => {
    const { user, loading } = useUser();

    if (loading) {
        return <div>Carregando...</div>; // TODO um loading component
    }

    if (!user || !allowedTypes.includes(user.type)) {
        return <Navigate to={redirectTo} replace />;
    }

    return <>{children}</>;
};