import { useEffect, useState } from "react";
import { ClientDashboard } from "@/components/dashboard/ClientDashboard";
import { CompanyDashboard } from "@/components/dashboard/CompanyDashboard";
import { AdminDashboard } from "@/components/dashboard/AdminDashboard";
import Auth from "./Auth";
import { useLocation } from "react-router-dom";
import { NotificationToast } from "@/components/notification/NotificationToast";
import { useUser } from "@/contexts/UserContext";
import { DashboardLayout } from "@/components/layout/DashboardLayout";

const Index = () => {
  const { user, loading, logout } = useUser();

  const [showNotification, setShowNotification] = useState(false);
  const location = useLocation();

  useEffect(() => {

    if (location.state?.fromRegister) {
      setShowNotification(true);
      window.history.replaceState({}, "");
    }
  }, [location]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!user) {
    return <Auth />;
  }

  const renderDashboard = () => {
    switch (user.type) {
      case "client":
        return <ClientDashboard />;
      case "company":
        return <CompanyDashboard />;
      case "admin":
        return <AdminDashboard />;
      default:
        return null;
    }
  };

  return (
    <DashboardLayout>
      {showNotification && (
        <NotificationToast
          message="Enviamos um e-mail com o link de confirmação. Verifique sua caixa de entrada."
          duration={8000}
        />
      )}

      {renderDashboard()}
    </DashboardLayout>
  );
};

export default Index;
