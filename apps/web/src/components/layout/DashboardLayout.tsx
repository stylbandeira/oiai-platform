import { useUser } from "@/contexts/UserContext";
import { DashboardHeader } from "@/components/dashboard/DashboardHeader";
import { Outlet } from "react-router-dom";

interface DashboardLayoutProps {
    children?: React.ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
    const { user, logout } = useUser();

    if (!user) {
        return (
            <div className="min-h-screen bg-background">
                <main>
                    {children || <Outlet />}
                </main>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background">
            <DashboardHeader
                userType={user.type}
                userName={user.name}
                userPoints={user.points}
                user={user}
                onLogout={logout}
            />
            <main>
                {children || <Outlet />}
            </main>
        </div>
    );
}